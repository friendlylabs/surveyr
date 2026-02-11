<?php

namespace App\Controllers\Api;
use App\Controllers\Api\BaseController;

use App\Models\Form;
use App\Models\Collection;

use Leaf\Database as DB;

class CollectionController extends BaseController
{
    
    public function __construct()
    {
        AuthController::authorize();
        parent::__construct();
    }

    /**
     * Stores a form submission
     * Save a single form submission
     * 
     * @param request $formId
     * @param request $content
     * 
     * @return void
     */
    public function store()
    {
        try{
            $formId = request()->params('formId');
            $form = Form::where(DB::$capsule::raw("MD5(id)"), $formId)->first();
            if(!$form) return self::jsonError("Form not found", 404);

            $content = json_decode($_REQUEST['content']) ?? null;
            if(!$content) return self::jsonError("Submission data is required");

            $data =[
                'form_id' => $form->id
            ];

            $collection = Collection::create($data);
            if(!$collection) return self::jsonError("Failed to save submission");

            # Create the payload
            $payloadData = [
                'collection_id' => $collection->id,
                'submission' => $content
            ];
            
            $payload = \App\Models\CollectionPayload::create($payloadData);
            if(!$payload) {
                $collection->delete(); // Clean up if payload creation fails
                return self::jsonError("Failed to save submission data");
            }

            return self::jsonSuccess("Submission saved successfully");
        }

        catch(\Exception $e){
            return self::jsonException($e);
        }
    }

    /**
     * Stores multiple form submissions
     * 
     * @param request $formId
     * @param request $content
     * 
     * @return void
     */
    public function multiple(){
        try{

            # validate form
            $formId = request()->params('formId');
            $form = Form::where(DB::$capsule::raw("MD5(id)"), $formId)->first();
            if(!$form) return self::jsonError("Form not found", 404);

            # validate content
            $content = json_decode($_REQUEST['content']) ?? null;
            if(!$content) return self::jsonError("Submission data field is empty or contains invalid data", 400);
            if(count($content) < 2) return self::jsonError("Submission must contain at least 2 items", 400);

            $collections = [];
            $payloads = [];
            
            # Create collections first
            foreach($content as $submission) {
                $collection = Collection::create(['form_id' => $form->id]);
                if(!$collection) return self::jsonError("Failed to save submissions", 500);
                
                $collections[] = $collection;
                $payloads[] = [
                    'collection_id' => $collection->id,
                    'submission' => $submission
                ];
            }
            
            # Bulk insert payloads
            $result = \App\Models\CollectionPayload::insert($payloads);
            if(!$result) {
                # Clean up collections if payload insertion fails
                foreach($collections as $collection) {
                    $collection->delete();
                }
                return self::jsonError("Failed to save submission data", 500);
            }

            return self::jsonSuccess("Submissions saved successfully");
        }

        catch(\Exception $e){
            return self::jsonException($e);
        }
    }


    public static function routes()
    {
        app()::post('/store', ['name'=>'api.collection.store', 'CollectionController@store']);
        app()::post('/store/multiple', ['name'=>'api.collection.mstore', 'CollectionController@multiple']);
    }
}