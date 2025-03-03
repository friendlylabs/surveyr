<?php

namespace App\Controllers\Base;

use App\Controllers\Controller;
use App\Models\Zone;
use Leaf\Database as DB;

class ZonesController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Show all zones
     * 
     * @return void
     */
    public function index()
    {
        $this->zones = Zone::all();
        return $this->renderPage('Data Zones', 'app.zones.index');
    }

    /**
     * Show a single zone
     * 
     * @param int $id
     * @return void
     */
    public function show(int $id)
    {
        $zone = Zone::find($id);
        if(!$zone) return $this->jsonError("Zone not found");

        $this->zone = $zone;
        return $this->renderPage($zone->name, 'app.zones.show');
    }

    /**
     * Store a new zone
     * 
     * @return void
     */
    public function store()
    {
        try{
            $data = [
                'name' => request()->params('name'),
                'description' => request()->params('description'),
                'user_id' => auth()->id()
            ];

            if(in_array(null, $data))
                return $this->jsonError("All fields are required");

            $zone = Zone::create($data);
            if(!$zone) return $this->jsonError("Failed to create zone");

            $this->redirect = route('zones.show', $zone->id);
            return $this->jsonSuccess("Zone created successfully");
        }

        catch(\Exception $e){
            return $this->jsonException($e);
        }
    }

    /**
     * Delete a zone
     * 
     * @param int $id
     * @return void
     */
    public function delete(int $id)
    {
        $zone = Zone::find($id);
        if(!$zone) 
            return redirect(route('zones.list'));

        $zone->delete();
        return redirect(route('zones.list'));
    }

    /**
     * Update a zone
     * 
     * @param int $id
     * @return void
     */
    public function update(int $id)
    {
        $zone = Zone::find($id);
        if(!$zone) 
            return $this->jsonError("Zone not found");

        $data = [
            'name' => request()->params('name', $zone->name),
            'description' => request()->params('description', $zone->description),

            'sheet' => !$_REQUEST['data'] ? $zone->data :$_REQUEST['data'],
            'content' => !$_REQUEST['content'] ? $zone->content : json_decode($_REQUEST['content']),
        ];

        if(in_array(null, $data))
            return $this->jsonError("All fields are required");

        $zone->update($data);
        return $this->jsonSuccess("Zone updated successfully");
    }

    /**
     * Public Zone
     * 
     * @param string $code
     * @return void
     */
    public function public(string $code){
        $zone = Zone::where(DB::$capsule::raw("MD5(id)"), $code)->first();
        if(!$zone) return $this->jsonError("Zone not found", 404);

        return response()->json($zone->content);
    }

    public static function routes()
    {
        app()::get('', ['name'=>'zones.list', 'ZonesController@index']);
        app()::get('show/{id}', ['name'=>'zones.show', 'ZonesController@show']);
        app()::get('delete/{id}', ['name'=>'zones.delete', 'ZonesController@delete']);

        app()::post('create', ['name'=>'zones.store', 'ZonesController@store']);
        app()::post('update/{id}', ['name'=>'zones.update', 'ZonesController@update']);
    }
}