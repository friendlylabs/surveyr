<?php

namespace App\Controllers\Base;

use App\Controllers\Controller;

use App\Models\FlagGroup;
use App\Models\Collection;

class FlagsController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    # List all flag groups
    public function index()
    {
        $this->groups = FlagGroup::listing();

        return $this->renderPage("Flags", "app.flags.index");
    }

    # Store a new flag group
    public function store()
    {
        try{
            $data = $this->validated();
            if(is_string($data)) return $this->jsonError($data, 200);

            $data['user_id'] = auth()->id();

            $group = FlagGroup::create($data);
            if(!$group) return $this->jsonError("Failed to create flag group");

            $this->redirect = route('flags.list');
            return $this->jsonSuccess("Flag group created successfully");
        }

        catch(\Exception $e){
            return $this->jsonException($e);
        }
    }

    # Update a flag group
    public function update($id)
    {
        try{
            $group = FlagGroup::find($id);
            if(!$group) return $this->jsonError("Flag group not found", 404);

            if(!FlagGroup::canManage($group))
                return $this->jsonError("You do not have permission to update this flag group", 403);

            $data = $this->validated($group);
            if(is_string($data)) return $this->jsonError($data, 200);

            $group->fill($data);
            if(!$group->save()) return $this->jsonError("Failed to update flag group");

            $this->redirect = route('flags.list');
            return $this->jsonSuccess("Flag group updated successfully");
        }

        catch(\Exception $e){
            return $this->jsonException($e);
        }
    }

    # Delete a flag group; forms using it lose their flag column (FK nulls it)
    public function delete($id)
    {
        $group = FlagGroup::find($id);
        if(!$group) return $this->errorPage(404);

        if(!FlagGroup::canManage($group)) return $this->errorPage(403);

        // the flag keys mean nothing without the group, so their submissions go unflagged
        Collection::whereIn('form_id', $group->forms()->pluck('id'))->update(['flag' => null]);

        $group->delete();
        return response()->redirect(route('flags.list'));
    }

    /**
     * Validate the posted group. Returns the fillable data, or an error message.
     * Flags arrive as flagKeys[] / flagLabels[] / flagColors[]; keys already on
     * the group are kept so submissions flagged with them stay linked.
     */
    protected function validated(?FlagGroup $group = null) : array|string
    {
        $name = trim((string) request()->params('name'));
        $description = trim((string) request()->params('description'));

        if(strlen($name) < 2 || strlen($name) > 50)
            return "The group name must be between 2 and 50 characters";

        $keys = (array) request()->params('flagKeys', []);
        $labels = (array) request()->params('flagLabels', []);
        $colors = (array) request()->params('flagColors', []);

        $known = $group ? $group->flagKeys() : [];
        $flags = [];
        $seen = [];

        foreach ($labels as $i => $label) {
            $label = trim((string) $label);
            if($label === '') continue;

            if(strlen($label) > 30) return "Flag labels can be at most 30 characters";

            $lower = strtolower($label);
            if(isset($seen[$lower])) return "Flag \"$label\" is listed twice";
            $seen[$lower] = true;

            $color = (string) ($colors[$i] ?? '');
            if(!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) return "Flag \"$label\" needs a valid colour";

            $key = (string) ($keys[$i] ?? '');
            if(!in_array($key, $known, true)) $key = substr(bin2hex(random_bytes(6)), 0, 8);

            $flags[] = ['key' => $key, 'label' => $label, 'color' => strtolower($color)];
        }

        if(!$flags) return "Add at least one flag";

        return [
            'name' => $name,
            'description' => $description !== '' ? $description : null,
            'flags' => $flags,
        ];
    }

    public static function routes()
    {
        app()::get('/', ['name'=>'flags.list', 'FlagsController@index']);
        app()::get('/delete/{id}', ['name'=>'flags.delete', 'FlagsController@delete']);

        app()::post('/store', ['name'=>'flags.store', 'FlagsController@store']);
        app()::post('/update/{id}', ['name'=>'flags.update', 'FlagsController@update']);
    }
}
