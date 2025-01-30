<?php

namespace App\Controllers\Api;

use App\Models\Form;
use App\Models\Space;

/**
 * 
 * API Base Controller
 *
 * This is the base controller for your Leaf MVC Project.
 * You can initialize packages or define methods here to use
 * them across all your other controllers which extend this one.
 * 
 */

 class BaseController extends \Leaf\Controller
 {
    protected static array $data = [];

    public function __construct()
    {
        parent::__construct();
    }

    public function __destruct()
    {
        session()->destroy();
    }
    

    public function __set(string $name, mixed $value): void
    {
        static::$data[$name] = $value;
    }

    /**
     * Respond with a JSON success message.
    *
    * @param string $message
    * @return Response
    */
    protected static function jsonSuccess(string $message)
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            ...static::$data,
        ]);
    }

    /**
     * Respond with a JSON error message.
    *
    * @param string $message
    * @param int $code
    * @return Response
    */
    protected static function jsonError(string $message, int $code = 400)
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            ...static::$data,
        ], $code);
    }

    /**
     * Handle exceptions and respond with a JSON error.
    *
    * @param Throwable $e
    * @return Response
    */
    protected static function jsonException(\Throwable $e)
    {
        $response = [
            'status' => false,
            'message' => 'An unexpected error occurred',
        ];

        if (filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOL)) {
            $response['debug'] = [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ];
        }

        return response()->json([...$response, ...static::$data]);
    }


    /* Form Helpers */

    /**
     * Check form ownership
     *
     * @param int $id
     * @return bool
     */
    public function formOwnerShipCheck($id) : bool
    {
        $form = Form::find($id);
        if(!$form) return false;

        return (auth()->user()->role == 'admin') || ($form->user_id == auth()->id()) || 
            in_array((string) auth()->id(), $form->collaborators);
    }

    /**
     * Check if user has access to form
     *
     * @param array $spaces
     * @return bool
     */
    public function hasAccessbySpace(array $spaces) : bool
    {
        $spaces = Space::whereIn('id', $spaces)
            ->where('user_id', auth()->id())
            ->orWhereJsonContains('members', (string) auth()->id())
            ->get();

        if($spaces->count()) return true;
        return false;
    }
}
