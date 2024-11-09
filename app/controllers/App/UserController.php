<?php

namespace App\Controllers\App;

use App\Models\User;
use Leaf\Helpers\Password;
use App\Controllers\Controller;

class UserController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function profile()
    {
        return $this->renderPage("Profile", 'app.user.profile');
    }

    public function updateProfile()
    {
        $username = request()->params('username');
        $fullname = request()->params('fullname');
        $email = request()->params('email');
        $avatar = request()->params('avatar');

        # Validate email
        if ($email && !$this->validateEmail($email)['status']) {
            return $this->jsonError("Invalid email address");
        }

        # Validate username
        if ($username && !$this->validateUsername($username)['status']) {
            return $this->jsonError("Invalid username format");
        }

        # Handle avatar upload
        if ($avatar && $avatar['size']) {

            $avatar = request()->uploadAs('avatar', StoragePath('app/public/avatars'), uniqid(),
                ['extensions' => ['jpg', 'jpeg', 'png']]
            );

            if (!$avatar) {
                return $this->jsonError("Invalid image format");
            }
            $avatar = str_replace(StoragePath('app/public'), '', $avatar['path']);
        }

        # Update user profile
        $user = User::find(auth()->id());
        $user->username = $username ?? $user->username;
        $user->fullname = $fullname ?? $user->fullname;
        $user->email = $email ?? $user->email;
        $user->avatar = $avatar ?? $user->avatar;

        if ($user->save()) {
            $this->setupLogedUser();
            return $this->jsonSuccess("Profile updated successfully");
        }

        return $this->jsonError("Profile update failed");
    }

    public function updatePassword()
    {
        $request = request()->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6',
            'confirm_password' => 'required'
        ]);

        if (!$request) {
            return $this->jsonError("Invalid password details");
        }

        if ($request['new_password'] !== $request['confirm_password']) {
            return $this->jsonError("New password and confirm password do not match");
        }

        $user = User::find(auth()->id());

        if (!Password::verify($request['current_password'], $user->password)) {
            return $this->jsonError("Invalid current password");
        }

        $user->password = Password::hash($request['new_password']);

        if ($user->save()) {
            return $this->jsonSuccess("Password updated successfully");
        }

        return $this->jsonError("Password update failed");
    }

    public function updateSecurity(){
        $request = request()->validate([
            'password' => 'required',
        ]);

        if (!$request) 
            return $this->jsonError("Please provide your password");


        $twoFa = request()->params('2fa', false) ? 1 : 0;
        $notifyLogin = request()->params('loging', false) ? 1 : 0;

        $user = User::find(auth()->id());

        if(!$user) return $this->jsonError("Invalid Request");

        if(Password::verify($request['password'], $user->password)){
            $user->two_fa = $twoFa;
            $user->notify_signin = $notifyLogin;
            $user->save();

            return $this->jsonSuccess("Security settings updated successfully");
        }else{
            return $this->jsonError("Invalid password provided");
        }
    }

    # Utility functions
    public function validateUsername($username) : array
    {
        if (!preg_match("/^[a-zA-Z0-9_]+$/", $username)) {
            return ['status' => false, 'message' => "Invalid username format"];
        }

        if (strlen($username) < 4 || strlen($username) > 20) {
            return ['status' => false, 'message' => "Username must be between 4 and 20 characters"];
        }

        if (User::where('username', $username)->where('id', '!=', auth()->id())->exists()) {
            return ['status' => false, 'message' => "Username already exists"];
        }

        return ['status' => true, 'message' => null];
    }

    public function validateEmail($email) : array
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['status' => false, 'message' => "Invalid email address"];
        }

        if (User::where('email', $email)->where('id', '!=', auth()->id())->exists()) {
            return ['status' => false, 'message' => "Email address already exists"];
        }

        return ['status' => true, 'message' => null];
    }
}