<?php

namespace App\Controllers\Base;
use App\Controllers\Controller;

use App\Models\ApiKey;
use Leaf\Helpers\Password;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class IntergrationController extends Controller {

    private static $user;

    public function __construct() {

        # verify the existence of the API module
        if(!SurveyrConfig('api.enabled')) {
            die( $this->jsonError("Please set up the API Module First", 403) );
        }

        # verify user has access to the resource
        static::$user = auth()->user();
        if(static::$user->role != 'admin') {
            die( $this->jsonError("You don't have access to this resource", 403) );
        }
        
        parent::__construct();
    }

    /**
     * Render the API Keys page
     *
     * @return void
     */
    public function index() {
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

    public static function routes() {
        app()->get('', ['name'=>'intergration.setup', 'IntergrationController@index']);
        app()->get('key/show/{id}', ['name'=>'keys.show', 'IntergrationController@show']);
        app()->get('key/revoke/{id}', ['name'=>'keys.revoke', 'IntergrationController@revoke']);

        app()->post('key/create', ['name'=>'keys.create', 'IntergrationController@create']);
    }
}