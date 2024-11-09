<?php

namespace App\Utils;

class GoogleUtil
{
    public static function authClient()
    {

        $authConfigsFile = AuthConfig('GOOGLE_APPLICATION_CREDENTIALS');
        if(!file_exists($authConfigsFile)){
            
            $authConfigsCredentials = [
                "web" => [
                    "client_id" => _env('GOOGLE_CLIENT_ID_KEY', 'your-client-id'),
                    "client_secret" => _env('GOOGLE_CLIENT_SECRET_KEY', 'your-client-secret'),
                ]
            ];

            file_put_contents($authConfigsFile, json_encode($authConfigsCredentials, 
                JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE
            ));

        }

        $client = new \Google\Client();
        $client->setAuthConfig($authConfigsFile);

        return $client;
    }
}