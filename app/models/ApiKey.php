<?php

namespace App\Models;

use Illuminate\Support\Carbon;

class ApiKey extends Model
{
    protected $table = 'api_keys';
    protected $fillable = [
        'name', 'user_id', 'token', 'secret'
    ];

    public $timestamps = true;
    protected $casts = [
        "created_at" => "datetime",
        "updated_at" => "datetime",
    ];

    protected $hidden = [
        'token', 'secret'
    ];

    protected static function booted()
    {
        // activities have no FK to the key: drop them with it
        static::deleting(fn (ApiKey $key) => $key->activities()->delete());
    }

    /**
     * Keys issued by a user, with the usage figures the list shows.
     *
     * @param int $user_id
     * @return object
     */
    public static function by(int $user_id) : object
    {
        return static::where('user_id', $user_id)
            ->withCount('activities')
            ->withMax('activities', 'created_at')
            ->orderByDesc('created_at')
            ->get();
    }

    # belongs to user
    public function user() : object
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    # has many activities
    public function activities() : object
    {
        return $this->hasMany(ApiActivity::class, 'apikey_id');
    }

    /**
     * HMAC key a passphrase signs tokens with. php-jwt needs 32+ bytes,
     * which a passphrase people can remember rarely is.
     *
     * @param string $passphrase
     * @return string
     */
    public static function signingKey(string $passphrase) : string
    {
        return hash('sha256', $passphrase);
    }

    /**
     * Whether a user may manage this key.
     *
     * @param object $user
     * @return bool
     */
    public function manageableBy(object $user) : bool
    {
        return $this->user_id == $user->id || $user->role == 'admin';
    }

    /**
     * Shortened token safe to show in lists.
     *
     * @return string
     */
    public function prefix() : string
    {
        return substr($this->token, 0, 12) . '…' . substr($this->token, -6);
    }

    /**
     * Expiry claim of the token (issued keys are JWTs).
     *
     * @return Carbon|null
     */
    public function expiresAt() : ?Carbon
    {
        $parts = explode('.', $this->token);
        if (count($parts) !== 3) return null;

        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        return isset($payload['exp']) ? Carbon::createFromTimestamp($payload['exp']) : null;
    }

    public function isExpired() : bool
    {
        $expiresAt = $this->expiresAt();
        return $expiresAt ? $expiresAt->isPast() : false;
    }

    /**
     * When the key was last used, from the eager-loaded max or the relation.
     *
     * @return Carbon|null
     */
    public function lastUsedAt() : ?Carbon
    {
        $value = array_key_exists('activities_max_created_at', $this->attributes)
            ? $this->attributes['activities_max_created_at']
            : $this->activities()->max('created_at');

        return $value ? Carbon::parse($value) : null;
    }
}
