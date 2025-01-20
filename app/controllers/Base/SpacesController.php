<?php

namespace App\Controllers\Base;

use App\Controllers\Controller;

use App\Models\Space;
use App\Models\User;
use App\Models\Form;

class SpacesController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    # List all spaces
    public function index()
    {
        $this->spaces = Space::userSpaces(auth()->id());
        $this->users = User::where('id', '!=', auth()->id())->get(); // TODO: to array

        return $this->renderPage("Spaces", "app.spaces.index");
    }

    # Show a space record
    public function show($id)
    {
        $space = Space::find($id);
        if(!$space) return $this->errorPage(404);

        if(!$this->spaceMemberCheck($id)) return $this->errorPage(403);

        $this->space = $space;
        $this->collabCol = false;
        $this->forms = Space::spaceForms($id);
        $this->users = User::where('id', '!=', auth()->id())->get();
        
        return $this->renderPage("Space: $space->name", "app.spaces.show");
    }

    # Store a new space record 
    public function store()
    {
        try{
            $data = [
                'name' => request()->params('spaceName'),
                'description' => request()->params('spaceDescription'),
                'color' => request()->params('spaceColor'),
                'user_id' => auth()->id()
            ];

            if(in_array(null, $data))
                return $this->jsonError("All fields are required");

            if(strlen($data['name']) < 3 || strlen($data['description']) < 10)
                return $this->jsonError("Name and description must be at least 3 and 10 characters, respectively");

            if(!is_null(request()->params('spaceMembers'))){
                $data['members'] = request()->params('spaceMembers');

                // make all items in the members array stored as strings, should be like ["1", "2", "3"]
                // Due to mysql bug: https://bugs.mysql.com/bug.php?id=79233
                // JSON array with integers is not fully supported in mysql with functions like JSON_SEARCH

                $data['members'] = array_map(function($item){
                    return (string)$item;
                }, $data['members']);
            }

            $space = Space::create($data);
            if($space){
                $this->space = $space;
                $this->redirect = route('spaces.list');
                return $this->jsonSuccess("Space created successfully", $space);
            }

            $this->jsonError("Failed to create space");
        }

        catch(\Exception $e){
            return $this->jsonException($e);
        }
    }

    # Update a space record
    public function update(){
        try{
            # validate space ownership
            if(!$this->spaceOwnerShipCheck(request()->params('spaceId')))
                return $this->jsonError("You do not have permission to update this space");

            # fetch and validate request data
            $data = request()->body();
            $space = Space::find($data['spaceId']);

            if(!$space) return $this->jsonError("Space not found");

            if(strlen($data['spaceName']) < 3 || strlen($data['spaceDescription']) < 10)
                return $this->jsonError("Name and description must be at least 3 and 10 characters, respectively");

            # update space
            $space->name = $data['spaceName'] ?? $space->name;
            $space->description = $data['spaceDescription'] ?? $space->description;
            $space->members = $data['spaceMembers'] ?? [];

            if($space->save())
                return $this->jsonSuccess("Space updated successfully", $space);
            

            return $this->jsonError("Failed to update space");
        }

        catch(\Exception $e){
            return $this->jsonException($e);
        }
    }

    # delete a space record
    public function delete($id)
    {
        $space = Space::find($id);
        if(!$space) return $this->errorPage(404);

        if(!$this->spaceOwnerShipCheck($id))
            return $this->jsonError("You do not have permission to delete this space");

        // remove space id from all forms referencing it
        Form::removeSpaceId($id);

        if($space->delete()){
            response()->redirect(route('spaces.list'));
        }
    }

    # verify space ownership
    protected function spaceOwnerShipCheck($space_id)
    {
        $space = Space::find($space_id);
        if(!$space) return false;

        if($space->user_id != auth()->id()) return false;

        return true;
    }

    # verify space membership
    protected function spaceMemberCheck($space_id)
    {
        $space = Space::find($space_id);
        if(!$space) return false;

        if($space->user_id != auth()->id() && !in_array(auth()->id(), $space->members)) return false;

        return true;
    }

    public static function routes()
    {
        app()::get('/', ['name'=>'spaces.list', 'SpacesController@index']);
        
        app()::get('/show/{id}', ['name'=>'spaces.show', 'SpacesController@show']);
        app()::get('/delete/{id}', ['name'=>'spaces.delete', 'SpacesController@delete']);
        
        app()::post('/store', ['name'=>'spaces.store', 'SpacesController@store']);
        app()::post('/update', ['name'=>'spaces.update', 'SpacesController@update']);
    }
}