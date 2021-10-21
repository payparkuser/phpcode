<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use DB, Hash, Setting, Validator, Exception, File;

use App\Helpers\Helper;

use App\Admin, App\Provider, App\User,App\Settings;

class DemoApiController extends Controller
{

    /**
     * @method admin_demo_login()
     * 
     * @uses to check admin login details
     *
     * @created Arun
     *
     * @updated 
     *
     * @param (request) setting details
     *
     * @return success/error message
     */
    public function admin_demo_login(Request $request) {

        try {

            $admin_demo_email = Setting::get('demo_admin_email');

            $admin_demo_password = Setting::get('demo_admin_password');

            $admin = Admin::where('email',$admin_demo_email)->first();

            if($admin) {

                if(Hash::check($admin_demo_password,$admin->password)){

                    $response_array = ['success' => true, 'message' => api_success(801),'data' => $admin];

                    return response()->json($response_array, 200);

                }

                throw new Exception(api_error(605), 605);
                

            }

            throw new Exception(api_error(601), 601);
            

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());

        }

    }

    /**
     * @method admin_demo_update()
     * 
     * @uses used to update the admin demo logins
     *
     * @created Arun
     *
     * @updated 
     *
     * @param (request) setting details
     *
     * @return success/error message
     */
    public function admin_demo_update(Request $request) {

        try {

            DB::beginTransaction();

            $demo_key = Settings::where('key','demo_admin_email')->first();
            
            if(!$demo_key) {

                throw new Exception('demo_admin_email'.' '.api_error(602), 602);
            
            }

            $demo_key = Settings::where('key','demo_admin_password')->first();
            
            if(!$demo_key) {

                throw new Exception('demo_admin_password'.' '.api_error(602), 602);
            
            }

            $admin_demo_email = Setting::get('demo_admin_email');

            $admin_demo_password = Setting::get('demo_admin_password');

            if($admin_demo_email && $admin_demo_password) {

                $admin = Admin::where('email',$admin_demo_email)->first();

                if(!$admin) {

                    $admin = new Admin;

                    $admin->email = $admin_demo_email;

                    $admin->password = Hash::make($admin_demo_password ?: "123456");

                    $admin->save();

                    DB::commit();

                    $response_array = ['success' => true, 'message' => api_success(802), 'data' => $admin];

                    return response()->json($response_array, 200);

                }
                
             $response_array = ['success' => true, 'message' => api_success(801),'data' => $admin];

             return response()->json($response_array, 200);

            }

            throw new Exception(api_error(601), 601);
            

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());

        }

    }

    /**
     * @method provider_demo_login()
     * 
     * @uses to check provider login details
     *
     * @created Arun
     *
     * @updated 
     *
     * @param (request) setting details
     *
     * @return success/error message
     */
    public function provider_demo_login(Request $request) {

        try {

            $provider_demo_email = Setting::get('demo_provider_email');

            $provider_demo_password = Setting::get('demo_provider_password');

            $provider = Provider::where('email',$provider_demo_email)->first();

            if($provider) {

                if(Hash::check($provider_demo_password, $provider->password)){

                    $response_array = ['success' => true, 'message' => api_success(803),'data' => $provider];

                    return response()->json($response_array, 200);

                }

                throw new Exception(api_error(605), 605);
                

            }

            throw new Exception(api_error(601), 601);
            

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());

        }

    }

    /**
     * @method provider_demo_update()
     * 
     * @uses used to update the provider demo logins
     *
     * @created Arun
     *
     * @updated 
     *
     * @param (request) setting details
     *
     * @return success/error message
     */
    public function provider_demo_update(Request $request) {

        try {

            DB::beginTransaction();

            $demo_key = Settings::where('key','demo_provider_email')->first();
            
            if(!$demo_key) {

                throw new Exception('demo_provider_email'.' '.api_error(602), 602);
            
            }

            $demo_key = Settings::where('key','demo_provider_password')->first();
            
            if(!$demo_key) {

                throw new Exception('demo_provider_password'.' '.api_error(602), 602);
            
            }

            $provider_demo_email = Setting::get('demo_provider_email');

            $provider_demo_password = Setting::get('demo_provider_password');

            if($provider_demo_email && $provider_demo_password) {

                $provider = Provider::where('email',$provider_demo_email)->first();

                if(!$provider) {

                    $provider = new Provider;

                    $provider->name = "Provider Demo";

                    $provider->email = $provider_demo_email;

                    $provider->password = Hash::make($provider_demo_password ?: "123456");

                    $provider->login_by = "Manual";

                    $provider->save();

                    DB::commit();

                    $response_array = ['success' => true, 'message' => api_success(804), 'data' => $provider];

                    return response()->json($response_array, 200);

                }
                
             $response_array = ['success' => true, 'message' => api_success(803),'data' => $provider];

             return response()->json($response_array, 200);

            }

            throw new Exception(api_error(601), 601);
            

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());

        }

    }

     /**
     * @method user_demo_login()
     * 
     * @uses to check user login details
     *
     * @created Arun
     *
     * @updated 
     *
     * @param (request) setting details
     *
     * @return success/error message
     */
    public function user_demo_login(Request $request) {

        try {

            $user_demo_email = Setting::get('demo_user_email');

            $user_demo_password = Setting::get('demo_user_password');

            $user = User::where('email',$user_demo_email)->first();

            if($user) {

                if(Hash::check($user_demo_password , $user->password)){

                    $response_array = ['success' => true, 'message' => api_success(805),'data' => $user];

                    return response()->json($response_array, 200);

                }

                throw new Exception(api_error(605), 605);
                

            }

            throw new Exception(api_error(601), 601);
            

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());

        }

    }

    /**
     * @method user_demo_update()
     * 
     * @uses used to update the user demo logins
     *
     * @created Arun
     *
     * @updated 
     *
     * @param (request) setting details
     *
     * @return success/error message
     */
    public function user_demo_update(Request $request) {

        try {

            DB::beginTransaction();

            $demo_key = Settings::where('key','demo_user_email')->first();
            
            if(!$demo_key) {

                throw new Exception('demo_user_email'.' '.api_error(602), 602);
            
            }

            $demo_key = Settings::where('key','demo_user_password')->first();
            
            if(!$demo_key) {

                throw new Exception('demo_user_password'.' '.api_error(602), 602);
            
            }
            $user_demo_email = Setting::get('demo_user_email');

            $user_demo_password = Setting::get('demo_user_password');

            if($user_demo_email && $user_demo_password) {

                $user = User::where('email',$user_demo_email)->first();

                if(!$user) {

                    $user = new User;

                    $user->name = "User Demo";

                    $user->email = $user_demo_email;

                    $user->password = Hash::make($user_demo_password ?: "123456");

                    $user->login_by = "Manual";

                    $user->save();

                    DB::commit();

                    $response_array = ['success' => true, 'message' => api_success(806), 'data' => $user];

                    return response()->json($response_array, 200);

                }
                
                $response_array = ['success' => true, 'message' => api_success(805),'data' => $user];

                return response()->json($response_array, 200);

            }

            throw new Exception(api_error(601), 601);
            

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());

        }

    }

    /**
     * @method setting_image_update()
     * 
     * @uses create or update demo login credetials.
     *
     * @created Arun
     *
     * @updated 
     *
     * @param (request) setting details
     *
     * @return success/error message
     */
    public function setting_image_update(Request $request){

        try {
            
            DB::beginTransaction();
            
            $rules = [
                'site_logo' => 'mimes:jpeg,jpg,bmp,png',
                'site_icon' => 'mimes:jpeg,jpg,bmp,png',
            ];

            $custom_errors = ['mimes' => api_error(604)];

            Helper::custom_validator($request->all(), $rules, $custom_errors);

            $data = new \stdClass();

            if( $request->hasFile('site_logo') ) {
                                        
                $file = Settings::where('key' ,'=', 'site_logo')->first();
               
                Helper::delete_file($file->value, FILE_PATH_SITE);

                $file_path = Helper::upload_file($request->file('site_logo') , FILE_PATH_SITE);    

                Settings::where('key' ,'=', 'site_logo')->update(['value' => $file_path]); 

                $site_logo = Settings::where('key' ,'=', 'site_logo')->first();

                $data->site_logo = $site_logo->value;

                if( $site_logo == TRUE ) {
             
                    DB::commit();
           
                } else {

                    throw new Exception(api_error(603), 603);
                } 
               
            } 

            if( $request->hasFile('site_icon') ) {
                                        
                $file = Settings::where('key' ,'=', 'site_icon')->first();
               
                Helper::delete_file($file->value, FILE_PATH_SITE);

                $file_path = Helper::upload_file($request->file('site_icon') , FILE_PATH_SITE);    

                Settings::where('key' ,'=', 'site_icon')->update(['value' => $file_path]); 

                $site_icon = Settings::where('key' ,'=', 'site_icon')->first();

                $data->site_icon = $site_icon->value;
                
                if( $site_icon == TRUE ) {
             
                    DB::commit();
           
                } else {

                    throw new Exception(api_error(603), 603);
                } 
               
            } 

            $response_array = ['success' => true, 'message' => api_success(808),'data' =>$data];

            return response()->json($response_array, 200);
            
        } catch (Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }

    /**
     * @method admin_demo_control_setting()
     * 
     * @uses 
     *
     * @created Arun
     *
     * @updated 
     *
     * @param (request) setting details
     *
     * @return success/error message
     */
    public function admin_demo_control_setting(Request $request) {

         try {
            
            DB::beginTransaction();
            
            $rules = [

                'demo_control' => 'required',
            ];

            $custom_errors = ['mimes' => api_error(604)];

            Helper::custom_validator($request->all(), $rules, $custom_errors);

            $demo_key = Settings::where('key','is_demo_control_enabled')->first();

            if(!$demo_key) {

                throw new Exception(api_error(602), 602);
                
            }

            Settings::where('key' ,'=', 'is_demo_control_enabled')->update(['value' => $request->demo_control]); 

            DB::commit();

            $site_icon = Settings::where('key' ,'=', 'is_demo_control_enabled')->first();

            $data = $site_icon->value == YES ? 'enabled' : 'disabled';

            $response_array = ['success' => true, 'message' => api_success(808),'data' =>$data];

            return response()->json($response_array, 200);
            
        } catch (Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }



}
