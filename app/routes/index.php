<?php

/*
|--------------------------------------------------------------------------
| Set up 404 handler
|--------------------------------------------------------------------------
|
| Leaf provides a default 404 page, but you can create your own
| 404 handler by uncommenting the code below. The function
| you set here will be called when a 404 error is encountered
|
*/
app()->set404(function() {

    # api and ajax based 404 handler
    if (strpos($_SERVER['REQUEST_URI'], '/api/') === 0 or request()->isXhr()) {
        return response()->json(['status'=>false, 'message' => 'Resource Not Found'], 404);
    }

    # web based 404 handler
 	return response()->markup(view("errors.404", ['title'=>'Resource Not Found']), 404);

});

/*
|--------------------------------------------------------------------------
| Set up 500 handler
|--------------------------------------------------------------------------
|
| Leaf provides a default 500 page, but you can create your own
| 500 handler by uncommenting the code below. The function
| you set here will be called when a 500 error is encountered
|
*/
if(_env('APP_DEBUG') === 'false'){
    app()->setErrorHandler(function() {
        
        # api and ajax 500 handler
        if (strpos($_SERVER['REQUEST_URI'], '/api/') === 0 or request()->isXhr()) {
            return response()->json(['status'=>false, 'message' => 'Server Error'], 500);
        }
        
        # web based 404 handler
        return response()->markup(view("errors.500", ['title'=>'Server Error']), 500);
    });
}


/*
|--------------------------------------------------------------------------
| Load the Auth middleware
|--------------------------------------------------------------------------
|
| This loads the auth middleware. The auth middleware is used to
| protect routes from unauthenticated users.
|
*/
\App\Middleware\Auth::load();

/*
|--------------------------------------------------------------------------
| Set up Controller namespace
|--------------------------------------------------------------------------
|
| This allows you to directly use controller names instead of typing
| the controller namespace first.
|
*/

app()->setNamespace('\App\Controllers');

/*
|--------------------------------------------------------------------------
| Your application routes
|--------------------------------------------------------------------------
|
| Leaf MVC automatically loads all files in the routes folder that
| start with "_". We call these files route partials. An example
| partial has been created for you.
|
| If you want to manually load routes, you can
| create a file that doesn't start with "_" and manually require
| it here like so:
|
*/
// require __DIR__ . '/custom-route.php';
