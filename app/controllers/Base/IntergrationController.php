<?php

namespace App\Controllers\Base;

#TODO: Refactor this controller to use the new API module

use App\Controllers\Controller;

use App\Models\ApiKey;
use App\Models\DeviceCode;

use Firebase\JWT\JWT;
use Leaf\Helpers\Password;

class IntergrationController extends Controller {

    private static $user;

    public function __construct() {
        
        static::$user = auth()->user();
        
        # verify the existence of the API module
        if(!SurveyrConfig('api.enabled')) {
            die( $this->jsonError("Please set up the API Module First", 403) );
        }
        
        parent::__construct();
    }

    /**
     * Render the API Keys page
     *
     * @return void
     */
    public function index() {
        $this->authenticate();

        $this->apiKeys = ApiKey::by(static::$user->id);
        return $this->renderPage('Api Keys', 'app.api.index');
    }

    /**
     * Generate a new API Key
     *
     * @return void
     */
    public function create() 
    {
        $this->authenticate();

        try{
            $name = request()->params('name');
            $passphrase = request()->params('passphrase');

            if(!$name || !$passphrase) {
                return $this->jsonError("Name and Passphrase are required");
            }

            # generate and save the token
            $token = $this->generateToken($passphrase);
            $apiKey = ApiKey::create([
                'name' => $name,
                'token' => $token,
                'user_id' => static::$user->id,
                'secret' => Password::hash($passphrase)
            ]);

            if(!$apiKey) {
                return $this->jsonError("Failed to generate API Key");
            }

            $this->redirect = route('intergration.setup');
            return $this->jsonSuccess("API Key generated successfully");
        }

        catch(\Exception $e){
            return $this->jsonException($e);
        }
    }

    /**
     * Generate a new API Key
     * 
     * @param string $passphrase
     * @return string
     */
    private function generateToken(string $passphrase) : string
    {
        $payload = [
            "iss" => "surveyr",
            "aud" => "surveyr",
            "iat" => time(),
            "nbf" => time(),
            "exp" => time() + (365 * 24 * 60 * 60),
            "data" => [
                "user_id" => static::$user->id
            ]
        ];

        return JWT::encode($payload, $passphrase, 'HS256');
    }

    /**
     * Show an API Key
     *
     * @param string $id
     * @return void
     */
    public function show(string $id) {
        return $this->errorPage(418);
    }

    /**
     * Revoke an API Key
     *
     * @param string $id
     * @return void
     */
    public function revoke(string $id) {
        $apiKey = ApiKey::find($id);
        if(!$apiKey) return $this->errorPage(404);

        // check if the user has access to the key
        if($apiKey->user_id != static::$user->id && static::$user->role != 'admin') {
            return $this->jsonError("You don't have access to this resource", 403);
        }

        $apiKey->delete();
        return redirect(route('intergration.setup'));
    }

    /**
     * Generate a device code
     *
     * @return void
     */
    public function deviceCode() {
        try{
            DeviceCode::reset(static::$user->id);

            $code = md5(bin2hex(random_bytes(5)));
            $deviceCode = DeviceCode::create([
                'code' => $code,
                'user_id' => static::$user->id
            ]);

            if(!$deviceCode) 
                return $this->jsonError("Failed to generate device code");
            
            $this->code = $code;
            return $this->jsonSuccess("Device code generated successfully");
        }

        catch(\Exception $e){
            return $this->jsonException($e);
        }
    }

    public function authenticate(){
        # verify user has access to the resource
        if(static::$user->role != 'admin') {
            die( $this->jsonError("You don't have access to this resource", 403) );
        }
    }    

    public static function routes() {
        app()->get('', ['name'=>'intergration.setup', 'IntergrationController@index']);
        app()->get('key/show/{id}', ['name'=>'keys.show', 'IntergrationController@show']);
        app()->get('key/revoke/{id}', ['name'=>'keys.revoke', 'IntergrationController@revoke']);

        app()->post('key/create', ['name'=>'keys.create', 'IntergrationController@create']);

        app()::get('device/code', ['name'=>'device.code', 'IntergrationController@deviceCode']);
    }
}