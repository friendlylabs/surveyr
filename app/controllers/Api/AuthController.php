<?php

namespace App\Controllers\Api;

use App\Models\ApiKey;
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
     * @return void
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
     * @return void
     */
    public static function authorize()
    {
        try {
            $token = self::extractToken();
            self::verifyPassphrase($token);         // if passphrase is provided
            $userId = self::validateToken($token);
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
        $token = str_replace('Bearer ', '', $headers['Authorization'] ?? null);

        if (!$token) {
            die(self::jsonError("Invalid token", 401));
        }

        return $token;
    }

    /**
     * Verify the passphrase signature if provided.
     *
     * @param string $token
     * @return void
     * @throws Exception
     */
    private static function verifyPassphrase(string $token): void
    {
        $passphrase = request()->params('passphrase');
        if ($passphrase) {
            $apiKey = ApiKey::where('token', $token)->first();
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
     * @return int
     * @throws Exception
     */
    private static function validateToken(string $token): int
    {
        try{
            $key = str_replace('base64:', '', _env('APP_KEY'));
            $passphrase = request()->params('passphrase') ?? $key;

            $tokenData = JWT::decode($token, new Key($passphrase, 'HS256'));
            $userId = $tokenData->data->user_id ?? null;

            $apiKey = ApiKey::where('token', $token)->first();
            if (!$userId || (request()->params('passphrase') && $userId != $apiKey->user_id ?? null)) {
                die(self::jsonError("Could not verify the Token signature", 401));
            }

            return $userId;
        }

        catch(\Exception $e){
            die(self::jsonError("Token Signature verification failed", 401));
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
    }

}