<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;

use App\Admin;

use DB, Setting, Hash, Validator, Exception;

use Carbon\Carbon;

class AdminLoginController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest:admin', ['except' => ['logout','reset_password']]);
    }

    /**
     * Show the application’s login form.
     *
     * @return \Illuminate\Http\Response
     */
    public function showLoginForm()
    {
        return view('admin.auth.login');
    }

    protected function guard() {

        return Auth::guard('admin');

    }
    
    use AuthenticatesUsers;

    // /**
    //  * Where to redirect users after login.
    // *
    // * @var string
    // */
    // protected $redirectTo = '/admin';

    // protected $redirectAfterLogout = '/admin/login';


    public function login(Request $request) {

        // Validate the form data
        $this->validate($request, [
            'email'   => 'required|email',
            'password' => 'required|min:6'
         ]);
      
        // Attempt to log the user in
        if (Auth::guard('admin')->attempt(['email' => $request->email, 'password' => $request->password], $request->remember)) {

            if(Auth::guard('admin')->check()) {

                if($admin = Admin::find(Auth::guard('admin')->user()->id)) {

                    $admin->timezone = $request->has('timezone') ? $request->timezone : '';

                    $admin->save();

                }  

            };

            // if successful, then redirect to their intended location
            return redirect()->intended(route('admin.dashboard'))->with('flash_success',tr('login_success'));
        } 
     
        // if unsuccessful, then redirect back to the login with the form data
     
        return redirect()->back()->withInput($request->only('email', 'remember'))->with('flash_error', tr('username_password_not_match'));
    }

    public function showLinkRequestForm() {
        
        try {

            $is_email_configured = YES;

            if(!envfile('MAIL_USERNAME') || !envfile('MAIL_PASSWORD') || !envfile('MAIL_FROM_ADDRESS') || !envfile('MAIL_FROM_NAME')) {

                $is_email_configured = NO;

                // throw new Exception(tr('email_not_configured'), 101);
                
            }

            return view('admin.auth.forgot')->with('is_email_configured', $is_email_configured);

        } catch(Exception $e){ 

            return redirect()->route('admin.login')->with('flash_error', $e->getMessage());

        } 
    }



    public function forgot_password_update(Request $request){

        try {
    
            DB::beginTransaction();
    
            // Check email configuration and email notification enabled by admin
    
            if(Setting::get('is_email_notification') != YES ) {
    
                throw new Exception(tr('email_not_configured'), 101);
                
            }
            
            $validator = Validator::make( $request->all(), [
                'email' => 'required|email|max:255|exists:admins',
            ]);
    
            if($validator->fails()) {
    
                $error = implode(',', $validator->messages()->all());
    
                throw new Exception($error, 101);
            }
    
            $admin = \App\Admin::where('email' , $request->email)->first();
    
            if(!$admin) {
    
                throw new Exception(tr('invalid_user'), 1002);
            }
    
            
            $token = app('auth.password.broker')->createToken($admin);
    
            \App\PasswordReset::where('email', $admin->email)->delete();
    
            \App\PasswordReset::insert([
                'email'=>$admin->email,
                'token'=>$token,
                'created_at'=>Carbon::now()
            ]);
    
            $email_data['subject'] = tr('reset_password_title' , Setting::get('site_name'));
    
            $email_data['email']  = $admin->email;
    
            $email_data['name']  = $admin->name;
    
            $email_data['user']  = $admin;
    
            $email_data['page'] = "emails.admin_reset_password";
    
            $email_data['url'] = url('/')."/admin/reset/password?token=".$token;
            
            $this->dispatch(new \App\Jobs\SendEmailJob($email_data));
    
            DB::commit();
    
            return redirect()->back()->with('flash_success',tr('mail_sent_success')); 
    
    
        } catch(Exception $e) {
    
            DB::rollback();
    
            return redirect()->back()->withInput()->with('flash_error', $e->getMessage());
    
        }
       }


       /**
     * @method reset_password
     *
     * @uses return view to reset password
     *
     * @created Ganesh
     *
     * @updated 
     *
     * @param object 
     * 
     * @return response return view page
     *
     **/

    public function reset_password() {
        
        Auth::guard('admin')->logout();

        return view('admin.auth.reset-password');

    }


    /**
     * @method reset_password_update()
     *
     * @uses To reset the password
     *
     * @created Ganesh
     *
     * @updated Ganesh
     *
     * @param object $request - Email id
     *
     * @return send mail to the valid store
     */
    
    public function reset_password_update(Request $request) {

        try {


            $validator = Validator::make( $request->all(), [
                'password' => 'required|confirmed|min:6',
                'password_confirmation'=>'required',
                'reset_token' => 'required|string'
            ]);
    
            if($validator->fails()) {
    
                $error = implode(',', $validator->messages()->all());
    
                throw new Exception($error, 101);
            }

            DB::beginTransaction();

            $password_reset = \App\PasswordReset::where('token', $request->reset_token)->first();

            if(!$password_reset){

                throw new Exception(tr('invalid_token'), 101);
            }
            
            $admin = \App\Admin::where('email', $password_reset->email)->first();

            $admin->password = \Hash::make($request->password);

            $admin->save();

            \App\PasswordReset::where('email', $admin->email) ->delete();

            DB::commit();

            // if successful, then redirect to their intended location
            return redirect()->route('admin.login')->with(['profile'=>$admin, 'flash_success'=>tr('password_change_success')]); 

        } catch(Exception $e) {

             DB::rollback();

            return redirect()->back()->withInput()->with('flash_error', $e->getMessage());
        }


   }

    public function logout() {

        Auth::guard('admin')->logout();
        
        return redirect()->route('admin.login')->with('flash_success',tr('logout_success'));;
    }

}