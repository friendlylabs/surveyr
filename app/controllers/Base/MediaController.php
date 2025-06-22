<?php

namespace App\Controllers\Base;
use App\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function store()
    {
        try {
            if(request()->get('attachment')['size'] > 0){
                $filePath = 'attachments/' . date('Y/m/d');
                $fileName = sha1(uniqid('att_', true));

                $attachment = request()->uploadAs(
                    'attachment',
                     StoragePath("app/public/{$filePath}"),
                     $fileName
                );

                if(!$attachment) return $this->jsonError("Failed to upload file", 500);
                $this->path = request()->getUrl() . "/storage/{$filePath}/{$attachment['name']}";

                return $this->jsonSuccess("File uploaded successfully");
            }

            return $this->jsonError("No file uploaded", 400);
        }
        catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }


    public static function routes()
    {
        app()::post('media', ['name' => 'media.store', 'MediaController@store']);
    }
}