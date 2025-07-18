<?php

namespace App\Controllers\Api;
use App\Controllers\Api\BaseController;

use App\Models\Zone;

class ZonesController extends BaseController
{
    public function __construct()
    {
        AuthController::authorize();
        parent::__construct();
    }

    public function index(){
        $zones = Zone::all()->mapWithKeys(function($zone) {
            return [md5($zone->id) => $zone->name];
        })->toArray();

        if(!$zones)
            return self::jsonError("No zones found", 200);

        $this->zones = $zones;
        return self::jsonSuccess("Zones fetched successfully");
    }

    public static function routes()
    {
        app()::get('', ['name' => 'api.zones.index', 'ZonesController@index']);
    }

}