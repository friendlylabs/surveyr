<?php

namespace App\Controllers\Base;
use App\Controllers\Controller;

use App\Models\User;
use App\Models\UserRole;
use App\Helpers\MailTool;

class UsersController extends Controller
{
    public function index(){

        # validate user role
        if(!in_array(auth()->user()->role, ['admin', 'moderator']))
            return $this->errorPage(403);

        # fetch and allocate data
        $users = User::all();
        $userRoles = UserRole::where('name', '!=', 'admin')
            ->where('name', '!=', 'moderator')
            ->get()->pluck('name')->toArray();

        $this->users = $users;
        $this->userRoles = $userRoles;

        return $this->renderPage('Users', 'app.users.index');
    }

    public function store(){
        try{
            # validate user role
            if(!in_array(auth()->user()->role, ['admin', 'moderator']))
                return $this->jsonError('You are not authorized to perform this action');

            $data = request()->body();
            if(auth()->user()->role == 'moderator'){
                if($data['role'] == 'admin' || $data['role'] == 'moderator')
                    return $this->jsonError('You are not authorized to create users with admin or moderator role');
            }

            # prepare data for user creation
            $user = new User();
            $user->username = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 8);            
            $user->fullname = $data['fullname'];
            $user->email = $data['email'];
            $user->role = $data['role'];
            $user->password = \Leaf\Helpers\Password::hash($data['password']);
            $user->status = 'active';
            $user->email_verified = 1;  

            if(!$user->save())
                return $this->jsonError('Failed to create user');

            # send welcome email
            if(AuthConfig('email.verify')){
                (new MailTool())->sendHtml('Welcome to Surveyor', view('mails.welcome', [
                    'name' => $data['fullname'],
                    'username' => $user['email'],
                    'password' => $data['password']
                ]), $user['email'], $user['fullname']);
            }

            $this->redirect = route('users.index');
            return $this->jsonSuccess('User created successfully');
            
        }

        catch(\Exception $e){
            return $this->jsonException($e);
        }
    }


    public function activate($id){
        # access control
        if(!in_array(auth()->user()->role, ['admin', 'moderator']))
            return $this->errorPage(403);

        $user = User::find($id);
        if(!$user) return $this->errorPage(404);

        # only an admin can modify the admin|moderator status
        if(auth()->user()->role == 'moderator'){
            if($user->role == 'admin' || $user->role == 'moderator')
                return $this->errorPage(403);
        }

        $user->status = 'active';
        $user->save();

        return response()->redirect(route('users.index'));
    }

    public function suspend($id){
        # access control
        if(!in_array(auth()->user()->role, ['admin', 'moderator']))
            return $this->errorPage(403);

        $user = User::find($id);
        if(!$user) return $this->errorPage(404);

        # only an admin can modify the admin|moderator status
        if(auth()->user()->role == 'moderator'){
            if($user->role == 'admin' || $user->role == 'moderator')
                return $this->errorPage(403);
        }

        $user->status = 'suspended';
        $user->save();

        return response()->redirect(route('users.index'));
    }

    public function update()
    {
        try{
            # validate user role
            if(!in_array(auth()->user()->role, ['admin', 'moderator']))
                return $this->jsonError('You are not authorized to perform this action');

            # fetch and validate request data
            $data = request()->body();
            $user = User::find($data['id']);
            if(!$user) return $this->jsonError('User not found');

            # only an admin can modify the admin|moderator
            if(auth()->user()->role == 'moderator'){
                if($data['role'] == 'admin' || $data['role'] == 'moderator')
                    return $this->jsonError('You are not authorized to create users with admin or moderator role');
            }

            # validate user roles
            $userRoles = UserRole::all()->pluck('name')->toArray();
            if(!in_array($data['role'], $userRoles))
                return $this->jsonError('Invalid user role');

            # update user
            $user->fullname = $data['fullname'];
            $user->email = $data['email'];
            $user->role = !$data['role'] ? $user->role : $data['role'];
            
            if($user->save())
                return $this->jsonSuccess('User updated successfully');
            
            return $this->jsonError('Failed to update user');
        }

        catch(\Exception $e){
            return $this->jsonException($e);
        }
    }

    public static function routes(){
        app()::get('', ['name'=>'users.index', 'UsersController@index']);
        app()::get('/suspend/{id}', ['name'=>'users.suspend', 'UsersController@suspend']);
        app()::get('/activate/{id}', ['name'=>'users.activate', 'UsersController@activate']);

        app()::post('/store', ['name'=>'users.store', 'UsersController@store']);
        app()::post('/update', ['name'=>'users.update', 'UsersController@update']);
    }
}