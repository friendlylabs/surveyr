<?php

namespace App\Controllers;

/**
 * This is the base controller for your Leaf MVC Project.
 * You can initialize packages or define methods here to use
 * them across all your other controllers which extend this one.
 */
class Controller extends \Leaf\Controller
{
    protected $data = [];

    public function __construct()
    {
        parent::__construct();
        $this->setupLogedUser();

        if(!request()->isAjax()) {
            $this->active = null;
            $this->title = null;
        }
    }

    public function __set(string $name, mixed $value)
    {
        $this->data[$name] = $value;
    }

    public function __get(string $name): mixed
    {
        property_exists($this, $name) ? 
            $data = $this->$name :
            $data = $this->data[$name] ?? null;

        return $data;
    }

    public function setupLogedUser()
    {
        if(auth()->id() and !request()->isAjax()) {
            $this->data['loggedUser'] = auth()->user();
            unset($this->data['loggedUser']->password);
        }
    }

    protected function renderPage(string $title, string $view, array $data = [])
    {
        $this->title = $title;
        return render($view, array_merge($this->data, $data));
    }

    protected function errorPage(int $code = 404)
    {
        return response()->markup(view("errors.$code"), $code);
    }

    protected function jsonResponse(bool $state, string $successMsg, string $errorMsg, string $redirect = null)
    {
        if ($state) {
            $this->status = true;
            $this->message = $successMsg;
        } else {
            $this->status = false;
            $this->message = $errorMsg;
        }

        return response()->json($this->data);
    }

    protected function jsonSuccess(string $message)
    {
        $this->status = true;
        $this->message = $message;
        return response()->json($this->data);
    }

    protected function jsonError(string $message, int $code = 400)
    {
        $this->status = false;
        $this->message = $message;
        return response()->json($this->data, $code);
    }

    protected function jsonException(\Exception $e)
    {
        $this->status = false;
        $this->message = "An unexpected error occurred";

        if (_env('APP_DEBUG') != 'false') {
            $this->debug = [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ];
        }

        return response()->json($this->data);
    }
}