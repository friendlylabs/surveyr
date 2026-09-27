<?php

namespace App\Models;

class ApiActivity extends Model
{
    protected $table = 'api_activities';
    protected $fillable = [
        'handler', 'origin', 'apikey_id', 'status'
    ];

    public $timestamps = true;
    protected $casts = [
        "created_at" => "datetime",
        "updated_at" => "datetime",
    ];

    # belongs to api key
    public function apikey() : object
    {
        return $this->belongsTo(ApiKey::class, 'apikey_id');
    }

    /**
     * Record the current request against an issued API key once the
     * response has been sent, so the logged status is the one the
     * client actually received (auth failures included).
     *
     * Only called for tokens that match a stored key: app sign-in
     * tokens are never tracked.
     *
     * @param int $apiKeyId
     * @return void
     */
    public static function track(int $apiKeyId) : void
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $handler = strtoupper(request()->getMethod()) . ' ' . $path;
        $origin = request()->getIp();

        register_shutdown_function(function () use ($apiKeyId, $handler, $origin) {
            try {
                static::create([
                    'apikey_id' => $apiKeyId,
                    'handler' => substr($handler, 0, 300),
                    'origin' => substr($origin, 0, 100),
                    'status' => (int) (http_response_code() ?: 200),
                ]);
            } catch (\Throwable $e) {
                // monitoring must never break an API response
            }
        });
    }

    /**
     * HTTP method and path of the tracked request.
     *
     * @return array [method, path]
     */
    public function route() : array
    {
        $parts = explode(' ', $this->handler, 2);
        return count($parts) === 2 ? $parts : ['', $this->handler];
    }

    /**
     * Outcome bucket of the logged status code.
     *
     * @return string success|client|server
     */
    public function outcome() : string
    {
        if ($this->status >= 500) return 'server';
        if ($this->status >= 400) return 'client';
        return 'success';
    }
}
