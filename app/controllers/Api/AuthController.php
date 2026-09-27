<?php

namespace App\Controllers\Api;

use App\Models\User;
use App\Models\ApiKey;
use App\Models\ApiActivity;
use App\Models\DeviceCode;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Leaf\Helpers\Password;

class AuthController extends BaseController
{
    protected static $user;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Log the user in.
     * 
     * @return void
     */
    public function signin(){
        try{
            $data = auth()->login([
                'email' => request()->get('email'),
                'password' => request()->get('password'),
                'status' => 'active'
            ]);

            if(!$data) return self::jsonError("Invalid login credentials", 401);

            static::$user = auth()->user();
            if(!static::$user->email_verified && AuthConfig('email.verify.enforce')){
                return self::jsonError("Please verify your email address", 401);
            }

            $token = $this->signinToken();
            if(!$token) return self::jsonError("Failed to sign in", 401);

            $this->token = $token;
            $this->projectId = md5(_env('APP_KEY'));
            $this->user = [
                'fullname' => static::$user->fullname,
                'email' => static::$user->email,
            ];

            return self::jsonSuccess("Signed in successfully");
        }

        catch(\Exception $e){
            return self::jsonException($e);
        }
            
    }

    /**
     * Verify a device code
     * 
     * @param string $code
     * @return void
     */
    function loginByScan($code){
        try{
            $code = DeviceCode::where('code', $code)->first();
            if(!$code) return self::jsonError("The code provided is invalid or Expired", 401);

            $userId = $code->user_id;
            DeviceCode::reset($userId);

            // get user
            static::$user = User::find($userId);

            // issue token
            $token = $this->signinToken();
            if(!$token) return self::jsonError("Failed to sign in", 401);
            
            $this->token = $token;
            $this->projectId = md5(_env('APP_KEY'));
            $this->user = [
                'fullname' => static::$user->fullname,
                'email' => static::$user->email,
            ];

            return self::jsonSuccess("Signed in successfully");
        }

        catch(\Exception $e){
            return self::jsonException($e);
        }
    }

    /**
     * Sigin Token
     *
     * @return string
     */
    protected function signinToken() : string
    {
        $payload = [
            "iss" => "surveyr",
            "aud" => "surveyr",
            "iat" => time(),
            "nbf" => time(),
            "exp" => time() + (90 * 24 * 60 * 60),
            "data" => [
                "user_id" => static::$user->id
            ]
        ];

        $key = str_replace('base64:', '', _env('APP_KEY'));
        return JWT::encode($payload, $key, 'HS256');
    }

    /**
     * Authorize the request.
     *
     * Two kinds of bearer tokens are accepted: sign-in tokens issued to the
     * app (signed with APP_KEY) and API keys issued from the integrations
     * page (signed with their passphrase). Only requests made with an issued
     * API key are recorded as activity.
     *
     * @return void
     */
    public static function authorize()
    {
        try {
            $token = self::extractToken();
            $apiKey = ApiKey::where('token', $token)->first();

            if ($apiKey) ApiActivity::track($apiKey->id);

            self::verifyPassphrase($apiKey);         // if passphrase is provided
            $userId = self::validateToken($token, $apiKey);
            self::authenticateUser($userId);
            return true;
        }
        
        catch (\Exception $e) {
            die(self::jsonException($e));
        }
    }

    /**
     * Extract the token from headers.
     *
     * @return string
     * @throws Exception
     */
    private static function extractToken(): string
    {
        $headers = getallheaders();
        $token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');

        if (!$token) {
            die(self::jsonError("No auth token provided", 401));
        }

        return $token;
    }

    /**
     * Verify the passphrase signature if provided.
     *
     * @param ApiKey|null $apiKey  the issued key matching the bearer token, if any
     * @return void
     * @throws Exception
     */
    private static function verifyPassphrase(?ApiKey $apiKey): void
    {
        $passphrase = request()->params('passphrase');
        if ($passphrase) {
            if (!$apiKey) {
                die(self::jsonError("Token could not be found from pre-Issued Tokens", 401));
            }

            if (!Password::verify($passphrase, $apiKey->secret)) {
                die(self::jsonError("Could not verify the Passphrase signature", 401));
            }
        }
    }

    /**
     * Validate the JWT token and extract the user ID.
     *
     * @param string $token
     * @param ApiKey|null $apiKey
     * @return int
     * @throws Exception
     */
    private static function validateToken(string $token, ?ApiKey $apiKey): int
    {
        try{
            $passphrase = request()->params('passphrase');

            $tokenData = $passphrase
                ? self::decodeIssuedKey($token, $passphrase)
                : JWT::decode($token, new Key(str_replace('base64:', '', _env('APP_KEY')), 'HS256'));
            $userId = $tokenData->data->user_id ?? null;

            if (!$userId || (request()->params('passphrase') && $userId != ($apiKey->user_id ?? null))) {
                die(self::jsonError("Could not verify the Token signature", 401));
            }

            return $userId;
        }

        catch(\Exception $e){
            die(self::jsonError("Token Signature verification failed", 401));
        }
    }

    /**
     * Decode an issued API key with its passphrase.
     *
     * Keys are signed with a digest of the passphrase (php-jwt requires
     * 32+ byte HMAC keys); keys issued before that were signed with the
     * raw passphrase, so fall back to it.
     *
     * @param string $token
     * @param string $passphrase
     * @return object
     * @throws \Exception
     */
    private static function decodeIssuedKey(string $token, string $passphrase): object
    {
        try {
            return JWT::decode($token, new Key(ApiKey::signingKey($passphrase), 'HS256'));
        } catch (\Exception $e) {
            return JWT::decode($token, new Key($passphrase, 'HS256'));
        }
    }

    /**
     * Authenticate the user using the extracted user ID.
     *
     * @param int $userId
     * @return void
     * @throws Exception
     */
    private static function authenticateUser(int $userId): void
    {
        auth()->config('password.key', false);
        $auth = auth()->login(['id' => $userId]);
        if (!$auth) {
            die(self::jsonError("Invalid user provided by the token", 401));
        }
    }

    public static function routes() :void
    {
        app()::post('/token', ['name'=>'auth.signin', 'AuthController@signin']);
        app()::get('/qr/{code}', ['name'=>'auth.scan', 'AuthController@loginByScan']);
    }

}