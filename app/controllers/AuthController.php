<?php

namespace App\Controllers;

use App\Utils\GoogleUtil;
use App\Controllers\Controller;

use App\Models\User;
use App\Models\UserToken;

use App\Helpers\MailTool;
use Leaf\Helpers\Password;

class AuthController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function login()
    {
        return $this->renderPage('Login', 'auth.login');
    }

    public function register()
    {
        if(!AuthConfig('allowRegistration'))
            return response()->redirect(route('login'));

        return $this->renderPage('Register', 'auth.register');
    }

    public function logout(){
        auth()->logout();
        response()->redirect(route('login'));
    }

    public function forgot()
    {
        return $this->renderPage('Reset Password', 'auth.forgot');
    }

    public function signin()
    {
        $data = auth()->login([
            'email' => request()->get('email'),
            'password' => request()->get('password'),
            'status' => 'active'
        ]);

        if($data){

            $user = auth()->user();

            if(!$user->email_verified && AuthConfig('email.verify.enforce')){
                auth()->logout();
                return response()->json([
                    'status' => false,
                    'message' => 'Please verify your email address',
                    'redirect' => route('login')
                ]);
            }

            if(!$user->two_fa):
                session()->set('session_id', md5(uniqid().time().$user->id));

                else:
                    $this->sendTwoFaToken();
                    $redirect = route('2fa');
            endif;

            if($user->notify_signin){
                (new MailTool())->sendHtml('New Signin', view('mails.signin', [
                    'name' => $user->fullname,
                    'ip' => request()->getIp(),
                ]), $user->email, $user->fullname);
            }
            
            $this->redirect = $redirect ?? route('app.home');
        }

        return $this->jsonResponse($data, "Welcome, Login successful", "Invalid login details");
    }

    public function signup()
    {
        if(!AuthConfig('allowRegistration'))
            return response()->redirect(route('login'));

        /*$request = request()->validate([
            'email' => 'required|email',
            'fullname' => 'required',
            'password' => 'required|min:6'
        ]);*/

        $request = [
            'email' => !filter_var(request()->params('email'), FILTER_VALIDATE_EMAIL) ? null : request()->params('email'),
            'fullname' => strlen(request()->params('fullname')) < 3 ? null : request()->params('fullname'),
            'password' => request()->params('password')
        ];

        if (in_array(null, $request) || strlen($request['password']) < 6 ) {
            return $this->jsonError("Invalid registration details");
        }

        if (User::where('email', request()->get('email'))->first()) {
            return $this->jsonError("User already exists");
        }

        $data = User::create([
            'email' => $request['email'],
            'username' => $this->generateUsername($request['email']),
            'fullname' => $request['fullname'],
            'password' => Password::hash($request['password'])
        ]);

        if(AuthConfig('email.verify') && $data){
            $verificationToken = Password::hash(uniqid() . time());

            UserToken::create([
                'user_id' => $data->id,
                'token' => $verificationToken,
                'type' => 'verify'
            ]);

            (new MailTool())->sendHtml('Email Verification', view('mails.verify', [
                'name' => $data->fullname,
                'token' => base64_encode($verificationToken)
            ]), $data->email, $data->fullname);
        }

        return $this->jsonResponse($data, "Registration successful", "Registration failed", route('login'));
    }

    public function twoFactor()
    {
        if(!auth()->id() || session()->has('session_id'))
            return response()->redirect(route('login'));

        // get the last 2fa token
        $token = UserToken::where('user_id', auth()->id())->where('type', '2fa')->first();
        $this->timer = $token ? 300 - (time() - strtotime($token->created_at)) : 0;
        
        return $this->renderPage('Two Factor Authentication', 'auth.2fa');
    }

    public function twoFactorSubmit()
    {
        // $request = request()->validate(['code' => 'required']);
        $request['code'] = request()->params('code');

        if (!$request['code']) {
            return $this->jsonError("Invalid 2FA code");
        }

        $user = User::find(auth()->id());
        if(!$user) return $this->jsonError("Invalid user session");

        # fetch 2fa token of type 2fa, resend if not found
        $token = UserToken::where('user_id', $user->id)->where('type', '2fa')->first();
        if(!$token || (time() - strtotime($token->created_at)) > 300){
            $this->sendTwoFaToken();
            return $this->jsonError("2FA code expired, new code sent to your email");
        }

        if(!Password::verify($request['code'], $token->token)){
            return $this->jsonError("Invalid 2FA code");
        }

        // delete 2fa token
        UserToken::where('token', $token->token)->delete();
        session()->set('session_id', md5(uniqid().time().$user->id));

        $this->redirect = route('app.home');
        return $this->jsonSuccess("2FA successful");
    }

    public function resendTwoFaToken()
    {
        try{
            // get the last 2fa token
            $token = UserToken::where('user_id', auth()->id())->where('type', '2fa')->first();
            $timer = $token ? 300 - (time() - strtotime($token->created_at)) : 0;

            if($timer > 0){
                return $this->jsonError("You can only resend 2FA after the current one expires");
            }

            //$this->sendTwoFaToken();
            $this->timer = 300;
            return $this->jsonSuccess("A new 2FA code sent to your email");
        }

        catch(\Exception $e){
            return $this->jsonException($e);
        }
    }

    private function sendTwoFaToken()
    {
        $user = auth()->user();
        $token = strtoupper(bin2hex(random_bytes(3)));

        UserToken::where('user_id', auth()->id())->where('type', '2fa')->delete();

        UserToken::create([
            'user_id' => auth()->id(),
            'token' => Password::hash($token),
            'type' => '2fa'
        ]);

        (new MailTool())->sendHtml('2FA Code', view('mails.2fa', [
            'name' => $user->fullname,
            'token' => $token
        ]), $user['email'], $user->fullname);
    }

    public function reset()
    {
        try {
            $request = request()->validate(['email' => 'email']);

            if (!$request) {
                return $this->jsonError("Invalid email address");
            }

            $userData = User::where('email', request()->get('email'))->first();
            if (!$userData) {
                return $this->jsonError("User with such email does not exist");
            }

            if ($userData->remember_token && (time() - strtotime($userData->updated_at)) < 60) {
                return $this->jsonError("Password reset link already sent, check spam folder");
            }

            $resetToken = Password::hash($userData->id . time());
            User::where('email', request()->get('email'))->update(['remember_token' => $resetToken]);

            (new MailTool())->sendHtml('Password Reset', view('mails.reset', [
                'name' => $userData->fullname,
                'token' => base64_encode($resetToken)
            ]), request()->get('email'), $userData->fullname);

            return $this->jsonSuccess('Password reset link sent to your email');
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    public function verify()
    {
        $token = request()->get('token');
        $tokenValidation = $this->validateToken($token, 'verify');

        if (!$tokenValidation['status']) {
            return response()->markup(view('errors.400'), 400);
        }

        $user = UserToken::where('token', $token)->first()->user;
        $user->email_verified = 1;
        $user->save();

        return $this->renderPage('Email Verified', 'auth.verified');
    }

    public function password($token)
    {
        $token = base64_decode($token);
        $user = User::where('remember_token', $token)->first();

        if (!$user || $this->isTokenExpired($user)) {
            return response()->markup(view('errors.400'), 400);
        }

        return $this->renderPage('Change Password', 'auth.reset', ['token' => base64_encode($token)]);
    }

    public function updatePassword()
    {
        // $request = $this->validateRequest(['password' => 'required|min:6', 'token' => 'required']);

        $request = [
            'password' => request()->params('password'),
            'token' => request()->params('token')
        ];

        if(!$request['password'] || !$request['token'])
            return $this->jsonError('Invalid password details');

        $user = User::where('remember_token', base64_decode($request['token']))->first();
        if (!$user || $this->isTokenExpired($user)) {
            return $this->jsonError('Invalid password reset token');
        }

        $user->password = Password::hash($request['password']);
        $user->remember_token = null;
        $user->save();

        return $this->jsonSuccess('Password updated successfully');
    }

    /*
    |--------------------------------------------------------------------------
    | Auth Helper Functions
    |--------------------------------------------------------------------------
    */

    private function validateRequest($rules)
    {
        return request()->validate($rules);
    }

    private function isTokenExpired($user)
    {
        return (time() - strtotime($user->updated_at)) > 7200;
    }

    private function validateToken($token, $type)
    {
        $userToken = UserToken::where('token', $token)->where('type', $type)->first();

        if (!$userToken) {
            return ['status' => false, 'message' => 'Invalid Token'];
        }

        if ((time() - strtotime($userToken->created_at)) > 7200) {
            return ['status' => false, 'message' => 'Token Has Expired'];
        }

        return ['status' => true, 'message' => null];
    }

    private function generateUsername($email)
    {
        $username = explode('@', $email)[0];
        $username = preg_replace('/[^A-Za-z0-9]/', '', $username);
        return $username . rand(1000, 9999);
    }

    /*
    |--------------------------------------------------------------------------
    | Google Auth
    |--------------------------------------------------------------------------
    */

    public function googleAuth()
    {
        $client = GoogleUtil::authClient();
        $client->setRedirectUri(routeUrl('google.callback'));
        $client->addScope("email");
        $client->addScope("profile");

        header('Location: ' . $client->createAuthUrl());
    }

    public function googleCallback()
    {
        try{
            $client = GoogleUtil::authClient();
            $client->setRedirectUri(routeUrl('google.callback'));

            if (request()->params('code', 0)) {
                $token = $client->fetchAccessTokenWithAuthCode(request()->get('code'));
                $client->setAccessToken($token);

                $service = new \Google\Service\Oauth2($client);
                $userData = $service->userinfo->get();

                $user = User::where('email', $userData->email)->first();

                if (!$user) {
                    $this->oauthData = $userData;
                    return $this->renderPage('Register', 'auth.register');
                }else{
                    auth()->config('password.key', false);
                    auth()->login(['id' => $user->id]);
                    session()->set('session_id', md5(uniqid().time().$user->id));
                }

                return response()->redirect(route('app.home'));
            }
        }

        catch (\Exception $e) {
            return response()->redirect(route('login'));
        }
    }   

    public static function routes()
    {
        app()->get('/login', ['name' => 'login', 'AuthController@login']);
        app()->get('/reset', ['name' => 'reset', 'AuthController@forgot']);
        app()->get('/logout', ['name' => 'logout', 'AuthController@logout']);
        app()->get('/register', ['name' => 'register', 'AuthController@register']);
        app()->get('/password/{token}', ['name' => 'password', 'AuthController@password']);

        app()->post('/login', ['name' => 'signin', 'AuthController@signin']);
        app()->post('/register', ['name' => 'signup', 'AuthController@signup']);
        app()->post('/reset', ['name' => 'reset', 'AuthController@reset']);
        app()->post('/password', ['name' => 'update.password', 'AuthController@updatePassword']);

        app()->get('/2fa', ['name' => '2fa', 'AuthController@twoFactor']);
        app()->post('/2fa', ['name' => '2fa', 'AuthController@twoFactorSubmit']);
        app()->get('/2fa/resend', ['name' => '2fa.resend', 'AuthController@resendTwoFaToken']);

        app()->get('/verify-email', ['name' => 'verify', 'AuthController@verify']);

        # 3rd party auth
        app()->get('/google', ['name' => 'google.auth', 'AuthController@googleAuth']);
        app()->get('/google/callback', ['name' => 'google.callback', 'AuthController@googleCallback']);
    }
}
