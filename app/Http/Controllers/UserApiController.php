<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Repositories\HostRepository as HostRepo;

use App\Helpers\Helper, App\Helpers\HostHelper;

use App\Http\Resources\HostCollection as HostCollection;

use App\Repositories\BookingRepository as BookingRepo;

use App\Repositories\PushNotificationRepository as PushRepo;

use Carbon\Carbon;

use Carbon\CarbonPeriod;

use DB, Log, Hash, Validator, Exception, Setting;

use App\Booking, App\BookingChat, App\BookingPayment;

use App\BookingProviderReview, App\BookingUserReview;

use App\ServiceLocation;

use App\ChatMessage;

use App\Host, App\HostDetails, App\HostAvailability, App\HostGallery, App\HostInventory;

use App\Lookups, App\StaticPage;

use App\Provider, App\ProviderDetails;

use App\User, App\UserCard, App\Wishlist;

use App\BellNotification;

use App\UserVehicle;

use App\Mail\ForgotPasswordMail, App\Mail\WelcomeMail;

use App\Jobs\BellNotificationJob, App\Jobs\SendEmailJob, App\Jobs\UserRatingJob, App\Jobs\UserBookingCancelJob, App\Jobs\BookingsCheckOutJob, App\Jobs\BookingsCheckInJob, App\Jobs\BookingsCreateJob;

use App\UserBillingInfo;

use App\Jobs\UserPaymentNotificationJob;

use App\Repositories\AccountRepository as AccRepo;

class UserApiController extends Controller {

    protected $loginUser;

    protected $skip, $take, $timezone, $currency, $push_notification_status, $device_type;

	public function __construct(Request $request) {

        Log::info(url()->current());

        Log::info("Request Data".print_r($request->all(), true));

        if($request->host_id) {

            $lists = ['space_id' => $request->host_id, 'price_type' => PRICE_TYPE_DAY, 'total_days' => 1];

            $request->request->add($lists);
        }

        $this->loginUser = User::CommonResponse()->find($request->id);

        $this->skip = $request->skip ?: 0;

        $this->take = $request->take ?: (Setting::get('admin_take_count') ?: TAKE_COUNT);

        $this->currency = Setting::get('currency', '$');

        $this->timezone = $this->loginUser->timezone ?? "America/New_York";

        Log::info("TimeZone".$this->timezone);

        $this->push_notification_status = $this->loginUser->push_notification_status ?? 0;

        $this->device_type = $this->loginUser->device_type ?? DEVICE_WEB;
    }

    /**
     * @method register()
     *
     * @uses Registered user can register through manual or social login
     * 
     * @created Vithya R 
     *
     * @updated Vithya R
     *
     * @param Form data
     *
     * @return Json response with user details
     */
    public function register(Request $request) {

        try {

            DB::beginTransaction();

            // Validate the common and basic fields

            $rules = 
                [
                    'device_type' => 'required|in:'.DEVICE_ANDROID.','.DEVICE_IOS.','.DEVICE_WEB,
                    'device_token' => 'required',
                    'login_by' => 'required|in:manual,facebook,google,apple',
                ];

            Helper::custom_validator($request->all(),$rules);

            $allowed_social_logins = ['facebook','google','apple'];

            if(in_array($request->login_by,$allowed_social_logins)) {

                // validate social registration fields

                $rules = [
                                'social_unique_id' => 'required',
                                'name' => 'required|max:255|min:2',
                                'email' => 'required|email|max:255',
                                'mobile' => 'digits_between:6,13',
                                'picture' => '',
                                'gender' => 'in:male,female,others',
                        ];

                Helper::custom_validator($request->all(),$rules);

            } else {

                // Validate manual registration fields

                $rules = 
                    [
                        'name' => 'required|max:255',
                        'email' => 'required|email|max:255|min:2',
                        'password' => 'required|min:6',
                        'picture' => 'mimes:jpeg,jpg,bmp,png',
                    ];

                Helper::custom_validator($request->all(),$rules);

                // validate email existence

                $rules = 
                    [
                        'email' => 'unique:users,email',
                    ];

                Helper::custom_validator($request->all(),$rules);

            }

            $user_details = User::where('email' , $request->email)->first();

            $send_email = NO;

            // Creating the user

            if(!$user_details) {

                $user_details = new User;

                register_mobile($request->device_type);

                $user_details->picture = asset('placeholder.jpg');

                $user_details->registration_steps = 1;

                $send_email = YES;

            } else {
                
                if(in_array($user_details->status , [USER_PENDING , USER_DECLINED])) {

                    throw new Exception(api_error(1000) , 1000);
                
                }

            }            

            if($request->has('name')) {

                $user_details->name = $request->name;

            }

            if($request->has('email')) {

                $user_details->email = $request->email;

            }

            if($request->has('mobile')) {

                $user_details->mobile = $request->mobile;

            }

            if($request->has('password')) {

                $user_details->password = Hash::make($request->password ?: "123456");

            }

            $user_details->gender = $request->has('gender') ? $request->gender : "male";

            $user_details->payment_mode = $request->payment_mode ?: $user_details->payment_mode;

            if(check_demo_login($user_details->email, $user_details->token)) {

                $user_details->token = Helper::generate_token();

            }

            $user_details->token_expiry = Helper::generate_token_expiry();

            $user_details->device_type = $request->device_type ?: DEVICE_WEB;

            $user_details->login_by = $request->login_by ?: 'manual';

            $user_details->social_unique_id = $request->social_unique_id ?: '';

            $user_details->timezone = $request->timezone ?: 'America/New_York';

            // Upload picture

            if($request->login_by == "manual") {

                if($request->hasFile('picture')) {

                    $user_details->picture = Helper::upload_file($request->file('picture') , PROFILE_PATH_USER);

                }

            } else {

                $user_details->is_verified = USER_EMAIL_VERIFIED; // Social login

                $user_details->picture = $request->picture ?: $user_details->picture;

            }   

            if($user_details->save()) {

                // Update the device token

                $check_device_exist = User::where('id', '!=', $user_details->id)->where('device_token', $request->device_token)->first();

                if($check_device_exist) {

                    $check_device_exist->device_token = "";

                    $check_device_exist->save();
                }

                $user_details->device_token = $request->device_token ?: "";

                $user_details->save();

                // send welcome email to the new user:

                if($send_email) {

                    if($user_details->login_by == 'manual') {

                        $user_details->password = $request->password;
    
                        $email_data['subject'] = tr('user_welcome_title').' '.Setting::get('site_name');

                        $email_data['page'] = "emails.users.welcome";

                        $email_data['data'] = $user_details;

                        $email_data['email'] = $user_details->email;

                        $this->dispatch(new SendEmailJob($email_data));

                    }

                }

                if(in_array($user_details->status , [USER_DECLINED , USER_PENDING])) {
                
                    $response = ['success' => false , 'error' => api_error(1000) , 'error_code' => 1000];

                    DB::commit();

                    return response()->json($response, 200);
               
                }

                if($user_details->is_verified == USER_EMAIL_VERIFIED) {

                    counter(); // For site analytics. Don't remove

                	$data = User::CommonResponse()->find($user_details->id);

                    $response_array = ['success' => true, 'message' => "Welcome ".$data->name, 'data' => $data];

                } else {

                    $response_array = ['success' => false, 'error' => api_error(1001), 'error_code'=>1001];

                    DB::commit();

                    return response()->json($response_array, 200);

                }

            } else {

                throw new Exception(api_error(103), 103);

            }

            DB::commit();

            return response()->json($response_array, 200);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());

        }
   
    }

    /**
     * @method login()
     *
     * @uses Registered user can login using their email & password
     * 
     * @created Vithya R 
     *
     * @updated Vithya R
     *
     * @param object $request - User Email & Password
     *
     * @return Json response with user details
     */
    public function login(Request $request) {

        try {

            DB::beginTransaction();

            $rules =
                [
                    'device_token' => 'required',
                    'device_type' => 'required|in:'.DEVICE_ANDROID.','.DEVICE_IOS.','.DEVICE_WEB,
                    'login_by' => 'required|in:manual,facebook,google, apple',
                ];

            Helper::custom_validator($request->all(),$rules);

            /** Validate manual login fields */

            $rules = 
                [
                    'email' => 'required|email',
                    'password' => 'required',
                ];

            Helper::custom_validator($request->all(),$rules);

            $user_details = User::where('email', '=', $request->email)->first();

            $email_active = DEFAULT_TRUE;

            // Check the user details 

            if(!$user_details) {

            	throw new Exception(api_error(1002), 1002);

            }

            // check the user approved status

            if($user_details->status != USER_APPROVED) {

            	throw new Exception(api_error(1000), 1000);

            }

            if(Setting::get('is_account_email_verification') == YES) {

                if(!$user_details->is_verified) {

                    Helper::check_email_verification("" , $user_details->id, $error,USER);

                    $email_active = DEFAULT_FALSE;

                }

            }

            if(!$email_active) {

    			throw new Exception(api_error(1001), 1001);
            }

            if(Hash::check($request->password, $user_details->password)) {

                // Generate new tokens

                if(check_demo_login($user_details->email, $user_details->token)) {

                    $user_details->token = Helper::generate_token();

                }

                $user_details->token_expiry = Helper::generate_token_expiry();
                
                // Update the device token

                $check_device_exist = User::where('id', '!=', $user_details->id)->where('device_token', $request->device_token)->first();

                if($check_device_exist) {

                    $check_device_exist->device_token = "";

                    $check_device_exist->save();
                }

                $user_details->device_token = $request->device_token ?: $user_details->device_token;

                $user_details->device_type = $request->device_type ?: $user_details->device_type;

                $user_details->login_by = $request->login_by ?: $user_details->login_by;

                $user_details->timezone = $request->timezone ?: $user_details->timezone;

                $user_details->save();

                counter(); // For site analytics. Don't remove

                DB::commit();

                $data = User::CommonResponse()->find($user_details->id);

                return $this->sendResponse(api_success(101), 101, $data);

            } else {

				throw new Exception(api_error(102), 102);
                
            }
            
        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());

        }
    
    }
 
    /**
     * @method forgot_password()
     *
     * @uses If the user forgot his/her password he can hange it over here
     *
     * @created Vithya R 
     *
     * @updated Vithya R
     *
     * @param object $request - Email id
     *
     * @return send mail to the valid user
     */
    
    public function forgot_password(Request $request) {

        try {

            DB::beginTransaction();

            // Check email configuration and email notification enabled by admin

            if(Setting::get('is_email_notification') != YES || envfile('MAIL_USERNAME') == "" || envfile('MAIL_PASSWORD') == "" ) {

                throw new Exception(api_error(106), 106);
                
            }
            
            $rules = 
                [
                    'email' => 'required|email|exists:users,email',
                ];
            $custom_errors =
                [
                    'exists' => 'The :attribute doesn\'t exists',
                ];

            Helper::custom_validator($request->all(),$rules, $custom_errors);

            $user_details = User::where('email' , $request->email)->first();

            if(!$user_details) {

                throw new Exception(api_error(1002), 1002);
            }

            if($user_details->login_by != "manual") {

                throw new Exception(api_error(116), 116);
                
            }

            // check email verification

            if($user_details->is_verified == USER_EMAIL_NOT_VERIFIED) {

                throw new Exception(api_error(1008), 1008);
            }

            // Check the user approve status

            if(in_array($user_details->status , [USER_DECLINED , USER_PENDING])) {
                throw new Exception(api_error(1000), 1000);
            }

            $new_password = Helper::generate_password();

            $user_details->password = Hash::make($new_password);

            $email_data['subject'] =  Setting::get('site_name').' '.tr('forgot_email_title');

            $email_data['page'] = "emails.users.forgot-password";

            $email_data['email'] = $user_details->email;

            $email_data['data'] = $new_password;

            $this->dispatch(new SendEmailJob($email_data));


            if(!$user_details->save()) {

                throw new Exception(api_error(103), 103);

            }

            DB::commit();

            $response_array = ['success' => true , 'message' => api_success(102)];

            return response()->json($response_array, 200);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        }
    
    }

    /**
     * @method change_password()
     *
     * @uses To change the password of the user
     *
     * @created Vithya R 
     *
     * @updated Vithya R
     *
     * @param object $request - Password & confirm Password
     *
     * @return json response of the user
     */
    public function change_password(Request $request) {

        try {

            DB::beginTransaction();

            $rules = [
                    'password' => 'required|confirmed|min:6',
                    'old_password' => 'required|min:6',
                ];

            Helper::custom_validator($request->all(),$rules);

            $user_details = User::find($request->id);

            if(!$user_details) {

                throw new Exception(api_error(1002), 1002);
            }

            if($user_details->login_by != "manual") {

                throw new Exception(api_error(119), 119);
                
            }

            if(Hash::check($request->old_password,$user_details->password)) {

                $user_details->password = Hash::make($request->password);
                
                if($user_details->save()) {

                    DB::commit();

                    return $this->sendResponse(api_success(104), $success_code = 104, $data = []);
                
                } else {

                    throw new Exception(api_error(103), 103);   
                }

            } else {

                throw new Exception(api_error(108) , 108);
            }

            

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());

        }

    }

    /**
     * @method update_billing_info()
     *
     * @uses Update the Account details
     *
     * @created Bhawya
     *
     * @updated Bhawya
     *
     * @param object $request - Account Details
     *
     * @return json response of the user
     */
    public function update_billing_info(Request $request) {

        try {

            DB::beginTransaction();

            $user_billing_info = UserBillingInfo::where('user_id',$request->id)->first() ?? new UserBillingInfo;

            $user_billing_info->user_id = $request->id;

            $user_billing_info->account_name = $request->account_name ?? "";

            $user_billing_info->paypal_email = $request->paypal_email ?? "";

            $user_billing_info->account_no = $request->account_no ?? "";

            $user_billing_info->route_no = $request->route_no ?? "";

            if($user_billing_info->save()) {

                DB::commit();

                $data = UserBillingInfo::find($user_billing_info->id);

                return $this->sendResponse(api_success(222), $success_code = 222, $data);
                
            } else {

                throw new Exception(api_error(228), 228);   
            }

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());

        }

    }

    /**
     * @method billing_info()
     *
     * @uses View the Account details
     *
     * @created Bhawya
     *
     * @updated Bhawya
     *
     * @param object $request - Account Details
     *
     * @return json response of the user
     */
    public function billing_info(Request $request) {

        try {

            $user_billing_info = UserBillingInfo::where('user_id',$request->id)->select('id as user_billing_info_id' , 'account_name' , 'paypal_email' ,'account_no', 'route_no' )->first();
            
            $data = $user_billing_info ?? [];

            return $this->sendResponse($message = "", $success_code = "", $data);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());

        }
    
    }
    /** 
     * @method profile()
     *
     * @uses To display the user details based on user  id
     *
     * @created Vithya R 
     *
     * @updated Vithya R
     *
     * @param object $request - User Id
     *
     * @return json response with user details
     */

    public function profile(Request $request) {

        try {

            $user_details = User::where('id' , $request->id)->CommonResponse()->first();

            if(!$user_details) { 

                throw new Exception(api_error(1002) , 1002);
            }

            $card_last_four_number = "";

            if($user_details->user_card_id) {

                $card = UserCard::find($user_details->user_card_id);

                if($card) {

                    $card_last_four_number = $card->last_four;

                }

            }

            $data = $user_details->toArray();

            $data['card_last_four_number'] = $card_last_four_number;

            return $this->sendResponse($message = "", $code = "", $data);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());

        }
    
    }
 
    /**
     * @method update_profile()
     *
     * @uses To update the user details
     *
     * @created Vithya R 
     *
     * @updated Vithya R
     *
     * @param objecct $request : User details
     *
     * @return json response with user details
     */
    public function update_profile(Request $request) {

        try {

            DB::beginTransaction();
            
            $rules = 
                [
                    'name' => 'required|max:255',
                    'email' => 'email|unique:users,email,'.$request->id.'|max:255',
                    'mobile' => 'digits_between:6,13',
                    'picture' => 'mimes:jpeg,bmp,png',
                    'gender' => 'in:male,female,others',
                    'device_token' => '',
                    'description' => ''
                ];

            Helper::custom_validator($request->all(),$rules);

            $user_details = User::find($request->id);

            if(!$user_details) { 

                throw new Exception(api_error(1002) , 1002);
            }

            $user_details->name = $request->name ?? $user_details->name;
            
            if($request->has('email')) {

                $user_details->email = $request->email;
            }

            $user_details->mobile = $request->mobile ?: $user_details->mobile;

            $user_details->gender = $request->gender ?: $user_details->gender;

            $user_details->description = $request->description ?: '';

            $user_details->timezone = $request->timezone ?: $user_details->timezone;

            // Upload picture
            if($request->hasFile('picture')) {

                Helper::delete_file($user_details->picture, COMMON_FILE_PATH); // Delete the old pic

                $user_details->picture = Helper::upload_file($request->file('picture') , COMMON_FILE_PATH);

            }

            if($user_details->save()) {

            	$data = User::CommonResponse()->find($user_details->id);

                DB::commit();

                return $this->sendResponse(api_success(214), $code = 214, $data );

            } else {    

        		throw new Exception(api_error(103) , 103);
            }

        } catch (Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());

        }
   
    }

    /**
     * @method delete_account()
     * 
     * @uses Delete user account based on user id
     *
     * @created Vithya R 
     *
     * @updated Vithya R
     *
     * @param object $request - Password and user id
     *
     * @return json with boolean output
     */

    public function delete_account(Request $request) {

        try {

            DB::beginTransaction();

            $request->request->add([ 
                'login_by' => $this->loginUser ? $this->loginUser->login_by : "manual",
            ]);
            
            $rules =
                [
                    'password' => 'required_if:login_by,manual',
                ]; 

            $custom_errors  = [
                    'password.required_if' => 'The :attribute field is required.',
                ];

            Helper::custom_validator($request->all(),$rules, $custom_errors);

            $user_details = User::find($request->id);

            if(!$user_details) {

            	throw new Exception(api_error(1002), 1002);
                
            }

            // The password is not required when the user is login from social. If manual means the password is required

            if($user_details->login_by == 'manual') {

                if(!Hash::check($request->password, $user_details->password)) {

                    $is_delete_allow = NO ;

                    $error = api_error(108);
         
                    throw new Exception($error , 108);
                    
                }
            
            }

            $delete_response = AccRepo::user_delete_response($user_details);

            if($delete_response['success'] == false) {

                throw new Exception($delete_response['error'], $delete_response['error_code']);
                
            }

            DB::commit();

            return $this->sendResponse(api_success(103), $code = 103, $data = []);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        }

	}

    /**
     * @method logout()
     *
     * @uses Logout the user
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param 
     * 
     * @return
     */
    public function logout(Request $request) {

        // @later no logic for logout

        return $this->sendResponse(api_success(106), 106);

    }

    /**
     * @method cards_list()
     *
     * @uses get the user payment mode and cards list
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param integer id
     * 
     * @return
     */

    public function cards_list(Request $request) {

        try {

            $user_cards = UserCard::where('user_id' , $request->id)->select('id as user_card_id' , 'customer_id' , 'last_four' ,'card_name', 'card_token' , 'is_default' )->get();

            $payment_modes = [];

            // $cod_data = [];

            // $cod_data['name'] = "COD";

            // $cod_data['payment_mode'] = COD;

            // $cod_data['is_default'] = $this->loginUser->payment_mode == COD ? YES : NO;

            // array_push($payment_modes , $cod_data);

            $card_data['name'] = "Card";

            $card_data['payment_mode'] = CARD;

            $card_data['is_default'] = $this->loginUser->payment_mode == CARD ? YES : NO;

            array_push($payment_modes , $card_data);

            $data['payment_modes'] = $payment_modes;   

            $data['cards'] = $user_cards ? $user_cards : []; 

            return $this->sendResponse($message = "", $code = "", $data);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());

        }
    
    }
    
    /**
     * @method cards_add()
     *
     * @uses Update the selected payment mode 
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param Form data
     * 
     * @return JSON Response
     */

    public function cards_add(Request $request) {

        try {

            if(Setting::get('stripe_secret_key')) {

                \Stripe\Stripe::setApiKey(Setting::get('stripe_secret_key'));

            } else {

                throw new Exception(api_error(133), 133);
            }
        
            $rules =
                    [
                        'card_token' => 'required',
                    ];

            Helper::custom_validator($request->all(),$rules);
            
            $user_details = User::find($request->id);

            if(!$user_details) {

                throw new Exception(api_error(1002), 1002);
                
            }

            DB::beginTransaction();

            // Get the key from settings table

            $customer = \Stripe\Customer::create([
                "email" => $user_details->email,
                "description" => "Customer for ".Setting::get('site_name'),
            ]);

            $stripe = new \Stripe\StripeClient(Setting::get('stripe_secret_key'));

            $intent = \Stripe\SetupIntent::create([
              'customer' => $customer->id,
              'payment_method' => $request->card_token
            ]);
           
            $stripe->setupIntents->confirm($intent->id,['payment_method' => $request->card_token]);

            $retrieve = $stripe->paymentMethods->retrieve($request->card_token, []);
            
            $card_info_from_stripe = $retrieve->card ? $retrieve->card : [];
            
            if($customer && $card_info_from_stripe) {

                $customer_id = $customer->id;

                $card_details = new UserCard;

                $card_details->user_id = $request->id;

                $card_details->customer_id = $customer_id;

                $card_details->last_four = $card_info_from_stripe->last4 ?? '';
                
                $card_details->card_token = $request->card_token ?? "NO-TOKEN";
                
                $card_details->card_name = $request->card_holder_name ?: $this->loginUser->name;

                // Check is any default is available

                $check_card_details = UserCard::where('user_id',$request->id)->count();

                $card_details->is_default = $check_card_details ? 0 : 1;

                if($card_details->save()) {

                    if($user_details) {

                        $user_details->user_card_id = $check_card_details ? $user_details->user_card_id : $card_details->id;

                        $user_details->save();
                    }

                    $data = UserCard::where('id' , $card_details->id)->select('id as user_card_id' , 'customer_id' , 'last_four' ,'card_name', 'card_token' , 'is_default' )->first();

                    DB::commit();

                    return $this->sendResponse(api_success(105), $code = 105, $data);

                } else {

                    throw new Exception(api_error(117), 117);
                    
                }
           
            } else {

                throw new Exception(api_error(117) , 117);
                
            }

        } catch(Stripe_CardError | Stripe_InvalidRequestError | Stripe_AuthenticationError | Stripe_ApiConnectionError | Stripe_Error $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode() ?: 101);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode() ?: 101);
        }
   
    }

    /**
     * @method cards_delete()
     *
     * @uses delete the selected card
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param integer user_card_id
     * 
     * @return JSON Response
     */

    public function cards_delete(Request $request) {

        // Log::info("cards_delete");

        DB::beginTransaction();

        try {
    
            $user_card_id = $request->user_card_id;

            $rules = 
                [
                    'user_card_id' => 'required|integer|exists:user_cards,id,user_id,'.$request->id,
                ];
            $custom_errors = [
                    'exists' => 'The :attribute doesn\'t belong to user:'.$this->loginUser->name
                ];

            Helper::custom_validator($request->all(),$rules);

            $user_details = User::find($request->id);

            // No need to prevent the deafult card delete. We need to allow user to delete the all the cards

            // if($user_details->user_card_id == $user_card_id) {

            //     throw new Exception(tr('card_default_error'), 101);
                
            // } else {

                UserCard::where('id',$user_card_id)->delete();

                if($user_details) {

                    if($user_details->payment_mode = CARD) {

                        // Check he added any other card

                        if($check_card = UserCard::where('user_id' , $request->id)->first()) {

                            $check_card->is_default =  DEFAULT_TRUE;

                            $user_details->user_card_id = $check_card->id;

                            $check_card->save();

                        } else { 

                            $user_details->payment_mode = COD;

                            $user_details->user_card_id = DEFAULT_FALSE;
                        
                        }
                   
                    }

                    // Check the deleting card and default card are same

                    if($user_details->user_card_id == $user_card_id) {

                        $user_details->user_card_id = DEFAULT_FALSE;

                        $user_details->save();
                    }
                    
                    $user_details->save();
                
                }

                $response_array = ['success' => true , 'message' => api_success(107) , 'code' => 107];

            // }



            DB::commit();
    
            return response()->json($response_array , 200);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }
    }

    /**
     * @method cards_default()
     *
     * @uses update the selected card as default
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param integer id
     * 
     * @return JSON Response
     */
    public function cards_default(Request $request) {

        Log::info("cards_default");

        try {

            DB::beginTransaction();

            $rules =
                [
                    'user_card_id' => 'required|integer|exists:user_cards,id,user_id,'.$request->id,
                ];
            $custom_errors =  
                [
                    'exists' => 'The :attribute doesn\'t belong to user:'.$this->loginUser->name
                ];

            Helper::custom_validator($request->all(),$rules, $custom_errors);

            $old_default_cards = UserCard::where('user_id' , $request->id)->where('is_default', DEFAULT_TRUE)->update(['is_default' => DEFAULT_FALSE]);

            $card = UserCard::where('id' , $request->user_card_id)->update(['is_default' => DEFAULT_TRUE]);

           //  $user_details = $this->loginUser;

            $user_details = User::find($request->id);

            $user_details->user_card_id = $request->user_card_id;

            $user_details->save();           

            DB::commit();

            return $this->sendResponse($message = api_success(108), $success_code = "108", $data = []);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }
    
    } 

    /**
     * @method payment_mode_default()
     *
     * @uses update the selected card as default
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param integer id
     * 
     * @return JSON Response
     */
    public function payment_mode_default(Request $request) {

        Log::info("payment_mode_default");

        try {

            DB::beginTransaction();

            $rules = 
                [
                    'payment_mode' => 'required',
                ];

            Helper::custom_validator($request->all(),$rules);

            $user_details = User::find($request->id);

            $user_details->payment_mode = $request->payment_mode ?: CARD;

            $user_details->save();           

            DB::commit();

            return $this->sendResponse($message = "Mode updated", $code = 200, $data = ['payment_mode' => $request->payment_mode]);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }
    
    } 

    /**
     * @method notification_settings()
     *
     * @uses To enable/disable notifications of email / push notification
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param - 
     *
     * @return JSON Response
     */
    public function notification_settings(Request $request) {

        try {

            DB::beginTransaction();

            $rules = 
                [
                    'status' => 'required|numeric',
                    'type' => 'required|in:'.EMAIL_NOTIFICATION.','.PUSH_NOTIFICATION
                ];

            Helper::custom_validator($request->all(),$rules);
                
            $user_details = User::find($request->id);

            if($request->type == EMAIL_NOTIFICATION) {

                $user_details->email_notification_status = $request->status;

            }

            if($request->type == PUSH_NOTIFICATION) {

                $user_details->push_notification_status = $request->status;

            }

            $user_details->save();

            $message = $request->status ? api_success(206) : api_success(207);

            $data = ['id' => $user_details->id , 'token' => $user_details->token];

            $response_array = [
                'success' => true ,'message' => $message, 
                'email_notification_status' => (int) $user_details->email_notification_status,  // Don't remove int (used ios)
                'push_notification_status' => (int) $user_details->push_notification_status,    // Don't remove int (used ios)
                'data' => $data
            ];
                
            
            DB::commit();

            return response()->json($response_array , 200);

        } catch (Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method configurations()
     *
     * @uses used to get the configurations for base products
     *
     * @created Vithya R Chandrasekar
     *
     * @updated - 
     *
     * @param - 
     *
     * @return JSON Response
     */
    public function configurations(Request $request) {

        try {

            $rules = [
                'id' => 'required|exists:users,id',
                'token' => 'required'
            ];

            Helper::custom_validator($request->all(),$rules);

            // Update timezone details

            $user_details = User::find($request->id);

            $message = "";

            if($user_details && $request->timezone) {
                
                $user_details->timezone = $request->timezone ?: $user_details->timezone;

                $user_details->save();

                $message = tr('timezone_updated');
            }

            $config_data = $data = [];

            $payment_data['is_stripe'] = 1;

            $payment_data['stripe_publishable_key'] = Setting::get('stripe_publishable_key') ?: "";

            $payment_data['stripe_secret_key'] = Setting::get('stripe_secret_key') ?: "";

            $payment_data['stripe_secret_key'] = Setting::get('stripe_secret_key') ?: "";

            $data['payments'] = $payment_data;

            $data['urls']  = [];

            $url_data['base_url'] = envfile("APP_URL") ?: "";

            $url_data['chat_socket_url'] = Setting::get("chat_socket_url") ?: "";

            $url_data['refund_page_url'] = route('static_pages.view', ['type' => 'refund']);

            $url_data['cancellation_page_url'] = route('static_pages.view', ['type' => 'cancellation']);

            $data['urls'] = $url_data;

            $notification_data['FCM_SENDER_ID'] = "";

            $notification_data['FCM_SERVER_KEY'] = $notification_data['FCM_API_KEY'] = "";

            $notification_data['FCM_PROTOCOL'] = "";

            $data['notification'] = $notification_data;

            // Bookings 

            $bookings_data['min_time'] = 60;

            $data['bookings'] = $bookings_data;

            $data['site_name'] = Setting::get('site_name');

            $data['site_logo'] = Setting::get('site_logo');

            $data['currency'] = $this->currency;

            return $this->sendResponse($message, $code = "", $data);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());

        }
   
    }

    /**
     *
     * @method see_all_section() 
     *
     * @uses used to get the first set of sections based on the page type
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param
     *
     * @return
     */

    public function see_all_section(Request $request) {

        Log::info("see_all_section".print_r($request->all(), true));

        try {

            switch ($request->api_page_type) {

                case API_PAGE_TYPE_RECENT_UPLOADED:
                    $hosts = HostHelper::recently_uploaded_hosts($request);
                    $title = tr('API_PAGE_TYPE_RECENT_UPLOADED');
                    $description = "";
                    break;

                case API_PAGE_TYPE_TOP_RATED:
                    $hosts = HostHelper::top_rated_hosts($request);
                    $title = tr('API_PAGE_TYPE_TOP_RATED');
                    $description = "";
                    break;

                case API_PAGE_TYPE_SUGGESTIONS:
                    $hosts = HostHelper::suggestions($request);
                    $title = tr('API_PAGE_TYPE_SUGGESTIONS');
                    $description = "";
                    break;

                default:
                    $hosts = HostHelper::suggestions($request);
                    $title = tr('API_PAGE_TYPE_SUGGESTIONS');
                    $description = "";
                    break;
            }

            $hosts_data['title'] = $title;

            $hosts_data['description'] = $description;

            $hosts_data['data'] = $hosts;

            $data = [];

            array_push($data, $hosts_data);

            return $this->sendResponse($message = "", $code = "", $data);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());

        }

    }
    /**
     * @method suggestions()
     *
     * @uses used get the hostings associated with selected category
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param object $request id, host_id
     *
     * @return response of details
     */

    public function suggestions(Request $request) {

        try {   
                
            $rules = [
                'space_id' => 'exists:hosts,id,status,'.APPROVED
            ];

            Helper::custom_validator($request->all(),$rules);

            $base_query = Host::orderBy('hosts.updated_at' , 'desc')->skip($this->skip)->take($this->take);

            if($request->space_id) {

                $base_query = $base_query->whereNotIn('hosts.id', [$request->space_id]);
            
            }

            $host_ids = $base_query->pluck('hosts.id');

            $hosts = [];

            if($host_ids) {
                $hosts = HostRepo::host_list_response($host_ids, $request->id);
            }

            return $this->sendResponse($message = "", $code = "", $hosts);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method hosts_availability()
     *
     * @uses used to get the host details
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param object $request
     *
     * @return response of details
     */
    /*public function hosts_availability(Request $request) {

        try {

            $request->request->add(['loops' => (int) $request->loops]);

            $validator = Validator::make($request->all(), [
                            'host_id' => 'required|exists:hosts,id',
                            'month' => 'required',
                            'year' => 'required',
                            'loops' => 'max:2|min:1',
                        ],[
                            'required' => Helper::error_message(202),
                            'exists.host_id' => Helper::error_message(200),
                        ]
                    );

            if($validator->fails()) {

                $error = implode(',', $validator->messages()->all());

                throw new Exception($error, 101);
                
            }

            $host = Host::where('hosts.id', $request->host_id)
                                ->where('hosts.is_admin_verified', ADMIN_SPACE_VERIFIED)
                                ->where('hosts.admin_status', ADMIN_SPACE_APPROVED)
                                ->where('hosts.status', SPACE_OWNER_PUBLISHED)
                                ->first();

            $host_details = HostDetails::where('host_id', $request->host_id)->first();

            if(!$host || !$host_details) {

                throw new Exception(Helper::error_message(200), 200);
                
            }

            $host_availabilities = HostAvailability::where('host_id', $request->host_id)->where('status', AVAILABLE)->get();

            $currency = $this->currency;

            $data = [];

            $data_ranges = HostHelper::generate_date_range($request->year, $request->month, "+1 day", "Y-m-d", $request->loops ?: 2);

            foreach ($data_ranges as $key => $data_range_details) {

                foreach ($data_range_details->dates as $check => $date_details) {

                    $availability_data = new \stdClass;

                    $check_host_availablity = HostAvailability::where('host_id', $request->host_id)->where('available_date', $date_details)->first();

                    $availability_data->date = $date_details;

                    $availability_data->is_available = $check_host_availablity ? $check_host_availablity->status: AVAILABLE;

                    $availability_data->is_blocked_booking = $check_host_availablity ? $check_host_availablity->is_blocked_booking : NO;

                    // The user can't book today date

                    if(strtotime($date_details) <= strtotime(date('Y-m-d'))) {
                        
                        $availability_data->is_available = NOTAVAILABLE;

                        $availability_data->is_blocked_booking = YES;

                    }

                    $availability_data->min_dates = $host_details->min_guests ?: 0;

                    $availability_data->max_dates = $host_details->max_guests ?: 0;

                    $price_details = new \stdClass;

                    $price_details->currency = $currency;

                    $price_details->price = $host->base_price;

                    $price_details->price_formatted = formatted_amount($host->base_price);

                    $price_details->per_day = $host->per_day;

                    $price_details->per_day_formatted = formatted_amount($host->per_day);

                    $availability_data->pricings = $price_details;

                    $now_data[] = $availability_data;

                }

                $first_month_data['title'] = $first_month_data['month'] = $data_range_details->month;

                $first_month_data['total_days'] = $data_range_details->total_days;

                $first_month_data['from_month'] = $request->month;

                // Todate find

                $to_date = Carbon::createFromDate($request->year, $request->month, 01)->addMonth($request->loops - 1)->day(01);

                $to_year = $to_date->year;

                $to_month = $to_date->month;

                $first_month_data['to_month'] = $to_month;

                $first_month_data['availability_data'] = $now_data;

                $data[] = $first_month_data;

            }

            return $this->sendResponse($message = "", $code = "", $data);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }*/

    /**
     * @method providers_view()
     *
     * @uses used to get the provider details
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param object $request
     *
     * @return response of details
     */
    public function providers_view(Request $request) {

        try {

            $provider_details = Provider::where('providers.status', PROVIDER_APPROVED)
                                    ->where('providers.is_verified', PROVIDER_EMAIL_VERIFIED)
                                    ->where('providers.id', $request->provider_id)
                                    ->FullResponse()
                                    ->first();

            if(!$provider_details) {

                throw new Exception(api_error(201), 201);
                
            }

            $provider_details->total_reviews = BookingUserReview::where('provider_id', $request->provider_id)->count();

            $provider_details->overall_ratings = BookingUserReview::where('provider_id', $request->provider_id)->avg('ratings') ?: 0;

            $host_ids = Host::VerifedHostQuery()->where('hosts.provider_id', $request->provider_id)->pluck('hosts.id')->toArray();

            $hosts = HostRepo::host_list_response($host_ids, $request->id);

            $provider_details->hosts = $hosts;

            $provider_details->total_hosts = count($hosts);

            return $this->sendResponse($message = "", $code = "", $provider_details);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method reviews_index()
     *
     * @uses used to get the reviews based review_type = provider | Host
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param object $request
     *
     * @return response of details
     */
    public function reviews_index(Request $request) {

        try {

            $rules = [
                'space_id' => 'exists:hosts,id',
                'provider_id' => 'exists:providers,id',
            ];

            $custom_errors = [
                'required' => Helper::error_message(202),
                'exists.space_id' => Helper::error_message(200),
                'exists.provider_id' => Helper::error_message(201)
            ];

            Helper::custom_validator($request->all(),$rules,$custom_errors);


            $base_query = BookingUserReview::leftJoin('users', 'users.id', '=', 'booking_user_reviews.user_id')
                                ->leftJoin('providers', 'providers.id', '=', 'booking_user_reviews.provider_id')
                                ->leftJoin('hosts', 'hosts.id', '=', 'booking_user_reviews.host_id')
                                ->select('booking_user_reviews.id as booking_user_review_id', 
                                        'hosts.host_name as space_name','booking_user_reviews.user_id','users.name as user_name', 
                                        'users.picture as user_picture', 'providers.name as provider_name',
                                        'providers.id as provider_id', 'providers.picture as provider_picture',
                                        'ratings', 'review', 'booking_user_reviews.created_at', 'booking_user_reviews.updated_at')
                                ->orderBy('booking_user_reviews.updated_at' , 'desc');

            if($request->space_id) {

                $basic_query = $base_query->where('booking_user_reviews.host_id', $request->space_id);

            }

            if($request->provider_id) {

                $basic_query = $base_query->where('booking_user_reviews.provider_id', $request->provider_id);

            }

            $reviews = $base_query->skip($this->skip)->take($this->take)->get();

            foreach ($reviews as $key => $review_details) {

                $review_details->updated = common_date($review_details->updated_at);
                
                $review_details->ratings = floatval(number_format($review_details->ratings + 0.01, 2));
            }

            return $this->sendResponse($message = "", $code = "", $reviews);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method other_users_view()
     *
     * @uses used to get the provider details
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param object $request
     *
     * @return response of details
     */
    public function other_users_view(Request $request) {

        try {

            $user_details = User::where('users.status', USER_APPROVED)->where('users.is_verified', USER_EMAIL_VERIFIED)
                                    ->where('users.id', $request->user_id)
                                    ->OtherCommonResponse()
                                    ->first();

            if(!$user_details) {

                throw new Exception(Helper::error_message(201), 201);
                
            }

            $user_reviews = BookingProviderReview::where('user_id', $request->user_id)->select('*', 'id as booking_user_review_id')->get();

            foreach ($user_reviews as $key => $value) {
                
            }

            $user_details->total_reviews = count($user_reviews);

            $user_details->reviews = $user_reviews;

            return $this->sendResponse($message = "", $code = "", $user_details);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method bookings_view()
     *
     * @uses used to get the list of bookings
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param object $request
     *
     * @return response of details
     */

    public function bookings_view(Request $request) {

        try {

            $rules = [
                'booking_id' => 'required|exists:bookings,id,user_id,'.$request->id,
            ];

            Helper::custom_validator($request->all(),$rules);

            $booking_details = Booking::where('user_id', $request->id)->where('id', $request->booking_id)->first();

            if(!$booking_details) {

                throw new Exception(api_error(206), 206); 
            }

            $host_details = Host::where('hosts.id', $booking_details->host_id)->VerifedHostQuery()->first();

            if(!$host_details) {

                throw new Exception(api_error(200), 200);
                
            }

            $user_details = $this->loginUser;

            $data = new \stdClass;

            $data->booking_id = $booking_details->id;

            $data->booking_unique_id = $booking_details->unique_id;

            $data->booking_description = $booking_details->description && $booking_details->description != "null" ? $booking_details->description : "";

            $data->space_id = $booking_details->host_id;

            $data->space_name = $host_details->host_name;

            $data->space_unique_id = $host_details->unique_id;

            $data->space_type = ucfirst($host_details->host_type);

            $data->picture = $host_details->picture;

            $data->wishlist_status = HostHelper::wishlist_status($booking_details->host_id, $request->id);

            $service_location_name = $host_details->serviceLocationDetails->name ?? '';

            $data->share_content = tr('share_content', Setting::get('site_name')).$service_location_name;
            
            $data->share_link = Setting::get('frontend_url')."host/booking-details/".$booking_details->id;

            $data->location = $host_details->serviceLocationDetails->name ?? "";

            $data->latitude = $host_details->latitude ?: "";

            $data->longitude = $host_details->longitude ?: "";

            $data->full_address = $host_details->full_address ?: "";

            $data->space_description = $host_details->description;

            $data->space_owner_type = userstring($host_details->host_owner_type ?? "");

            $data->space_dimension = $host_details->dimension ?? '';

            $data->total_spaces = $host_details->total_spaces ?? 0;

            $data->is_automatic_booking = $booking_details->is_automatic_booking;

            $data->booking_type_text = $booking_details->is_automatic_booking ? tr('automatic_booking') : tr('manual_booking');

            $data->access_note = $host_details->access_note ?: "";

            $data->access_method = userstring($host_details->access_method) ?? "";

            $data->security_code = $host_details->security_code ?: "";


            $data->total_days = $booking_details->total_days;

            $data->checkin_time = common_date($booking_details->checkin, $this->timezone, "h:i A");

            $data->checkout_time = common_date($booking_details->checkout, $this->timezone, "h:i A");

            $data->checkin = common_date($booking_details->checkin, $this->timezone, "d M Y");

            $data->checkout = common_date($booking_details->checkout, $this->timezone, 'd M Y');

            $data->duration = $booking_details->duration;

            $data->overall_ratings = $host_details->overall_ratings ?: 0;

            $data->total_ratings = $host_details->total_ratings ?: 0;

            $data->currency = $booking_details->currency;

            $data->total = $booking_details->total;

            $data->checkin_verification_code = $booking_details->checkin_verification_code ?? "";

            $data->total_formatted = formatted_amount($booking_details->total);

            $data->price_type = HostHelper::formatted_price_type($booking_details->price_type);

            $host_galleries = HostGallery::where('host_id', $host_details->id)->select('picture', 'caption')->get();

            $data->gallery = $host_galleries;

            $provider_details = Provider::where('id', $host_details->provider_id)
                                        ->select('id as provider_id', 
                                            'username as provider_name', 'email', 'picture', 'mobile', 'description','created_at')
                                        ->first();

            $data->provider_details = $provider_details ?? [];

            $booking_payment_details = $booking_details->bookingPayments ?: new BookingPayment;

            $pricing_details = new \stdClass();

            $pricing_details->currency = $this->currency;


            $pricing_details->payment_id = $booking_payment_details->payment_id ?: "";

            $pricing_details->payment_mode = $booking_payment_details->payment_mode ?: "CARD";

            $pricing_details->per_hour = $host_details->per_hour ?: 0.00;

            $pricing_details->per_hour_formatted = formatted_amount($host_details->per_hour);


            $pricing_details->per_day = $host_details->per_day ?: 0.00;

            $pricing_details->per_day_formatted = formatted_amount($host_details->per_day);

            $pricing_details->per_month = $host_details->per_month ?: 0.00;

            $pricing_details->per_month_formatted = formatted_amount($host_details->per_month);

            $pricing_details->price_type_amount = $booking_details->price_type == PRICE_TYPE_MONTH ? $booking_payment_details->per_month : ($booking_details->price_type == PRICE_TYPE_DAY  ? $booking_payment_details->per_day : $booking_payment_details->per_hour);

            $pricing_details->price_type_amount_formatted = formatted_amount($pricing_details->price_type_amount);

            $pricing_details->tax_price = $booking_payment_details->tax_price ?: 0.00;

            $pricing_details->tax_price_formatted = formatted_amount($booking_payment_details->tax_price);

            $pricing_details->paid_amount = $booking_payment_details->paid_amount ?: 0.00;

            $pricing_details->paid_amount_formatted = formatted_amount($booking_payment_details->paid_amount ?: 0.00);

            $pricing_details->paid_date = $booking_payment_details->paid_date ? common_date($booking_payment_details->paid_date,$this->timezone) : '';

            $pricing_details->total_amount = $booking_payment_details->actual_total ?: 0.00;

            $pricing_details->total_amount_formatted = formatted_amount($booking_payment_details->actual_total ?? 0.00);

            $data->pricing_details = $pricing_details;

            $data->status_text = booking_status($booking_details->status);

            $data->buttons = booking_btn_status($booking_details->status,$booking_details->id,USER, $booking_details->is_automatic_booking);

            $data->vehicle_details = UserVehicle::CommonResponse()->where('user_vehicles.id', $booking_details->user_vehicle_id)->first() ?? '';

            $data->cancelled_date = common_date($booking_payment_details->cancelled_date ?: date('Y-m-d'));

            $data->cancelled_reason = $booking_details->cancelled_reason;

            $reviews = BookingUserReview::where('booking_id', $request->booking_id)->select('review', 'ratings', 'id as booking_review_id')->first();

            $data->reviews = $reviews ?: [];

            return $this->sendResponse($message = "", $code = "", $data);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method bookings_cancel()
     *
     * @uses used to get the list of bookings
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param object $request
     *
     * @return response of details
     */
    public function bookings_cancel(Request $request) {

        try {

            $rules = [

                'booking_id' => 'required|exists:bookings,id,user_id,'.$request->id
            ];

            Helper::custom_validator($request->all(),$rules);
            
            $booking_details = Booking::where('bookings.id', $request->booking_id)->where('user_id', $request->id)->first();

            if(!$booking_details) {

                throw new Exception(api_error(206), 206);
            }

            // check the required status to cancel the booking

            $cancelled_status = [BOOKING_CANCELLED_BY_USER, BOOKING_CANCELLED_BY_PROVIDER];

            if(in_array($booking_details->status, $cancelled_status)) {

                throw new Exception(api_error(209), 209);
                
            }

            // After checkin the user can't cancel the booking 

            if($booking_details->status == BOOKING_CHECKIN) {
                
                throw new Exception(api_error(217), 217);

            }

            DB::beginTransaction();

            $booking_details->status = BOOKING_CANCELLED_BY_USER;

            $booking_details->cancelled_reason = $request->cancelled_reason ?: "";

            $booking_details->cancelled_date = date('Y-m-d H:i:s');

            if($booking_details->save()) {

                // Reduce the provider amount from provider redeems
                BookingRepo::revert_provider_redeems($booking_details);

                // Add refund amount to the user
                BookingRepo::add_user_refund($booking_details);

                BookingRepo::bookings_cancel_revert_availability($booking_details);

                DB::commit();

                $job_data['booking_details'] = $booking_details;
                
                $job_data['timezone'] = $this->timezone;
                
                $this->dispatch(new UserBookingCancelJob($job_data));

                $data = ['booking_id' => $booking_details->id];

                return $this->sendResponse(api_success(213), $code = 213, $data);

            } else {
                
                throw new Exception(api_error(208), 208);

            }

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method bookings_rating_report()
     *
     * @uses used to get the list of bookings
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param object $request
     *
     * @return response of details
     */
    public function bookings_rating_report(Request $request) {

        try {

            $rules = [
                'booking_id' => 'required|exists:bookings,id', 
                'ratings' => 'required|min:1',
                'review' => 'required'
            ];

            Helper::custom_validator($request->all(),$rules);

            DB::beginTransaction();

            // Check the booking is exists and belongs to the logged in user

            $booking_details = Booking::where('user_id', $request->id)->where('id', $request->booking_id)->first();

            if(!$booking_details) {

                throw new Exception(api_error(206), 206);
                
            }

            // Check the booking review completed already

            if($booking_details->status == BOOKING_COMPLETED) {
                
                throw new Exception(api_error(218), 218);
            }

            // Check the booking is eligible for review

            if($booking_details->status != BOOKING_CHECKOUT) {

                throw new Exception(api_error(214), 214);
                
            }

            // Check the user already rated

            $check_user_review = BookingUserReview::where('booking_id', $request->booking_id)->count();

            if($check_user_review) {

                throw new Exception(api_error(218), 218);
                
            }

            $review_details = new BookingUserReview;

            $review_details->user_id = $booking_details->user_id;

            $review_details->provider_id = $booking_details->provider_id;

            $review_details->host_id = $booking_details->host_id;

            $review_details->booking_id = $booking_details->id;

            $review_details->ratings = $request->ratings ?: 0;

            $review_details->review = $request->review ?: "";

            $review_details->status = APPROVED;

            if($review_details->save()) {

                DB::commit();

                $booking_details->status = BOOKING_COMPLETED;

                $booking_details->save();

                // Update total ratings & overall_ratings of host

                $host_details =  Host::find($booking_details->host_id);

                if($host_details) {

                    $host_details->total_ratings += 1;

                    $host_details->overall_ratings = BookingUserReview::where('host_id', $booking_details->host_id)->avg('ratings') ?: $host_details->overall_ratings;

                    $host_details->save();

                }

                $data = ['booking_id' => $request->booking_id, 'booking_provider_review_id' => $review_details->id];

                $job_data['booking_details'] = $booking_details;

                $job_data['timezone'] = $this->timezone;

                $this->dispatch(new UserRatingJob($job_data));
                
                $message = api_success(216); $code = 216; 

                return $this->sendResponse($message, $code, $data);
            }

            throw new Exception(api_error(219), 219);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());

        }

    }


    /**
     * @method bookings_checkin()
     *
     * @uses used to update the checkout status of booking
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param object $request
     *
     * @return response of details
     */

    public function bookings_checkin(Request $request){

        try {
            
            $rules = [

                'booking_id' => 'required|exists:bookings,id,user_id,'.$request->id
            ];

            Helper::custom_validator($request->all(),$rules);
            
            $booking_details = Booking::where('bookings.id', $request->booking_id)->where('user_id', $request->id)->first();

            if(!$booking_details) {

                throw new Exception(api_error(206), 206);
            }

            // Check the booking is already checked-in

            if($booking_details->status == BOOKING_CHECKIN) {

                throw new Exception(api_error(222), 222);
                
            }

            // Check the booking is eligible for checkin

            if($booking_details->is_automatic_booking == YES && $booking_details->status != BOOKING_DONE_BY_USER) {

                throw new Exception(api_error(234), 234);
                
            } elseif ($booking_details->is_automatic_booking == NO && $booking_details->status != BOOKING_APPROVED_BY_PROVIDER) {

                throw new Exception(api_error(234), 234);
                
            }

            $actual_checkin = new \DateTime("now", new \DateTimeZone("UTC"));

            $time_difference = date_convertion($booking_details->checkin, $actual_checkin->format('Y-m-d H:i:s'));
            
            if($time_difference->days > 0) {

                throw new Exception(api_error(235), 235);

            }
                
            // CheckTime Validation- Before 10 mins only User can checkin
            $check_time = (strtotime($booking_details->checkin) - time())/60; 

            if($check_time > 20) {

                throw new Exception(api_error(236), 236);

            }

            DB::beginTransaction();

            $booking_details->status = BOOKING_CHECKIN;

            $booking_details->checkin = $actual_checkin->format('Y-m-d H:i:s');
            
            if($booking_details->save()) {

                DB::commit();

                $job_data['booking_details'] = $booking_details;

                $job_data['timezone'] = $this->timezone;

                $this->dispatch(new BookingsCheckInJob($job_data));

                $data = ['booking_id' => $booking_details->id, 'checkin' => common_date($booking_details->checkin)];

                return $this->sendResponse(api_success(218), 218, $data);

            } else {

                throw new Exception(api_error(221), 221);
            }

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method bookings_checkout()
     *
     * @uses used to update the checkout status of booking
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param object $request
     *
     * @return response of details
     */

    public function bookings_checkout(Request $request){

        try {

            $rules = [

                'booking_id' => 'required|exists:bookings,id,user_id,'.$request->id
            ];

            Helper::custom_validator($request->all(),$rules);
            
            $booking_details = Booking::where('bookings.id', $request->booking_id)->first();

            if(!$booking_details) {

                throw new Exception(api_error(206), 206);
            }

            // check the booking is already checked-out

            if($booking_details->status == BOOKING_CHECKOUT) {

                throw new Exception(api_error(225), 225);
                
            }

            // check the booking is eligible for checkout

            if($booking_details->status != BOOKING_CHECKIN) {

                throw new Exception(api_error(223), 223);
                
            }

            DB::beginTransaction();

            $current_time = new \DateTime("now", new \DateTimeZone("UTC"));

            // $check_out = common_server_date(date("Y-m-d H:i:s"), $this->timezone, 'Y-m-d H:i:s');

            $check_out = $current_time->format('Y-m-d H:i:s');

            if($booking_details->price_type == PRICE_TYPE_HOUR) {

                $actual_hours = total_hours($booking_details->checkin, $booking_details->checkout);

                $extra_hours = total_hours($booking_details->checkin, $check_out);

                if($actual_hours < $extra_hours) {

                    $total_hours = $extra_hours - $actual_hours;

                    $booking_payment_response = BookingRepo::additional_hours_payment($booking_details, $total_hours)->getData();

                    if($booking_payment_response->success == false) {

                        throw new Exception($booking_payment_response->error, $booking_payment_response->error_code); 

                    }

                    // Notification for User - Extra hours Payment
                    
                    $job_data['total_hours'] = $total_hours;

                    $job_data['booking_details'] = $booking_details;

                    $this->dispatch(new UserPaymentNotificationJob($job_data));
                }
            
            }
            
            $booking_details->status = BOOKING_CHECKOUT;

            $booking_details->checkout = $check_out;

            if($booking_details->save()) {

                DB::commit();                

                $job_data['booking_details'] = $booking_details;

                $job_data['timezone'] = $this->timezone;
                
                $this->dispatch(new BookingsCheckOutJob($job_data));

                $data = ['booking_id' => $booking_details->id, 'checkout' => common_date($booking_details->checkout), 'status_text' => booking_status($booking_details->status)];

                return $this->sendResponse(api_success(219), 219, $data);

            } else {

                throw new Exception(api_error(224), 224);
            }

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }
    /**
     * @method bookings_inbox()
     *
     * @uses used to get the list of bookings
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param object $request
     *
     * @return response of details
     */
    public function bookings_inbox(Request $request) {

        try {

            $chat_messages = ChatMessage::where('user_id' , $request->id)
                        ->select('host_id as space_id', 'provider_id', 'booking_id', 'type', 'type as chat_type', 'updated_at', 'message')
                        ->groupBy('provider_id')
                        ->orderBy('updated_at' , 'desc')
                        ->skip($this->skip)
                        ->take($this->take)
                        ->get();

            foreach ($chat_messages as $key => $chat_message_details) {

                $provider_details = Provider::find($chat_message_details->provider_id);

                $chat_message_details->provider_name = $provider_details->name ?? "";

                $chat_message_details->provider_picture = $provider_details->picture ?? "";

                $chat_message_details->updated = $chat_message_details->updated_at->diffForHumans();
                
            }

            return $this->sendResponse($message = "", $code = "", $chat_messages);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method bookings_chat_details()
     *
     * @uses used to get the messages for selected Booking
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param object $request
     *
     * @return response of details
     */
    public function bookings_chat_details(Request $request) {

        try {
            
            $rules =
                [
                    'space_id' => 'required', 
                    'provider_id' => 'required',
                    'booking_id' => $request->booking_id > 0 ? 'exists:bookings,id' : "", 
                ];

            Helper::custom_validator($request->all(),$rules);

            $base_query = ChatMessage::select('chat_messages.id as chat_message_id', 'booking_id', 'host_id as space_id', 'provider_id', 'user_id', 'type', 'type as chat_type','updated_at', 'message');

            // if($request->booking_id) {

            //     $base_query = $base_query->where('chat_messages.booking_id' , $request->booking_id);

            // }

            // if($request->space_id) {

            //     $base_query = $base_query->where('chat_messages.host_id' , $request->space_id);

            // }

            if($request->provider_id) {

                $base_query = $base_query->where('chat_messages.provider_id' , $request->provider_id);

            }

            $chat_messages = $base_query->orderBy('chat_messages.updated_at' , 'desc')
                    ->skip($this->skip)->take($this->take)
                    ->get();

            foreach ($chat_messages as $key => $chat_message_details) {

                $provider_details = Provider::find($chat_message_details->provider_id);

                $chat_message_details->provider_name = $chat_message_details->provider_picture = "";

                $chat_message_details->updated = $chat_message_details->updated_at->diffForHumans();

                if($provider_details) {

                    $chat_message_details->provider_name = $provider_details->username;

                    $chat_message_details->provider_picture = $provider_details->picture;

                }
                
            }

            return $this->sendResponse($message = "", $code = "", $chat_messages);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method bookings_upcoming()
     *
     * @uses used to get the list of bookings
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param object $request
     *
     * @return response of details
     */
    public function bookings_upcoming(Request $request) {
        
        try {

            $upcoming_status = [BOOKING_ONPROGRESS, BOOKING_DONE_BY_USER, BOOKING_CHECKIN,BOOKING_APPROVED_BY_PROVIDER];

            $booking_ids = Booking::where('bookings.user_id' , $request->id) 
                                ->whereIn('bookings.status', $upcoming_status)
                                ->orderBy('bookings.id' , 'desc')
                                ->skip($this->skip)->take($this->take)
                                ->pluck('bookings.id');

            $bookings = BookingRepo::user_booking_list_response($booking_ids, $request->id, $this->timezone);

            return $this->sendResponse($message = "", $code = "", $bookings);
            
        } catch(Exception  $e) {
            
            return $this->sendError($e->getMessage(), $e->getCode());

        }

    } 

    /**
     * @method bookings_history ()
     *
     * @uses used to get the list of bookings
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param object $request
     *
     * @return response of details
     */
    public function bookings_history(Request $request) {

        try {

            $history_status = [BOOKING_CANCELLED_BY_USER, BOOKING_CANCELLED_BY_PROVIDER, BOOKING_COMPLETED, BOOKING_REFUND_INITIATED, BOOKING_CHECKOUT];

            $booking_ids = Booking::where('bookings.user_id' , $request->id) 
                            ->whereIn('bookings.status', $history_status)
                            ->orderBy('bookings.id' , 'desc')
                            ->skip($this->skip)->take($this->take)
                            ->pluck('bookings.id');

            $bookings = BookingRepo::user_booking_list_response($booking_ids, $request->id, $this->timezone);

            return $this->sendResponse($message = "", $code = "", $bookings);

        } catch(Exception $e) {
            
            return $this->sendError($e->getMessage(), $e->getCode());

        }

    }

    /**
     * @method wishlist_list()
     *
     * @uses Get the user saved the hosts
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param object $request id
     *
     * @return response of details
     */
    public function wishlist_list(Request $request) {

        try {

            $wishlists = Wishlist::where('user_id' , $request->id)
                            ->orderBy('updated_at' , 'desc')
                            ->skip($this->skip)
                            ->take($this->take)
                            ->CommonResponse()
                            ->get();

            foreach ($wishlists as $key => $wishlist_details) {

                $wishlist_details->wishlist_status = YES;

                $wishlist_details->per_day_formatted = formatted_amount($wishlist_details->per_day);

                $wishlist_details->per_day_symbol = tr('list_per_day_symbol');

                $wishlist_details->per_hour_formatted = formatted_amount($wishlist_details->per_hour);

                $wishlist_details->per_hour_symbol = tr('list_per_hour_symbol');
            }

            return $this->sendResponse($message = "", $code = "", $wishlists);

        } catch(Exception  $e) {
            
            return $this->sendError($e->getMessage(), $e->getCode());

        }

    }

    /**
     * @method wishlist_operations()
     *
     * @uses To add/Remove by using this operation favorite
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param object $request id, host_id
     *
     * @return response of details
     */
    public function wishlist_operations(Request $request) {

        try {

            DB::beginTransaction();

            $rules = [
                'clear_all_status' => 'in:'.YES.','.NO,
                'space_id' => $request->clear_all_status == NO ? 'required|exists:hosts,id,status,'.APPROVED : '', 
            ];
            

            $custom_errors =   [
                'required' => api_error(200)
            ];

            Helper::custom_validator($request->all(),$rules, $custom_errors);

            if($request->clear_all_status == YES) {

                Wishlist::where('user_id', $request->id)->delete();
                
                DB::commit();

                return $this->sendResponse($message = api_success(202), $code = 202, $data = []);


            } else {

                $wishlist_details = Wishlist::where('host_id', $request->space_id)->where('user_id', $request->id)->first();

                if($wishlist_details) {

                    if($wishlist_details->delete()) {

                        DB::commit();

                        $data = ['wishlist_status' => NO, 'space_id' => $request->space_id];

                        return $this->sendResponse($message = api_success(201), $code = 201, $data);

                    } else {

                        throw new Exception(api_error(216), 216);
                      
                    }

                } else {

                    $wishlist_details = new Wishlist;

                    $wishlist_details->user_id = $request->id;

                    $wishlist_details->host_id = $request->space_id;

                    $wishlist_details->status = APPROVED;

                    $wishlist_details->save();

                    DB::commit();

                    $data = ['wishlist_id' => $wishlist_details->id, 'wishlist_status' => $wishlist_details->status, 'space_id' => $request->space_id];
               
                    return $this->sendResponse(api_success(200), 200, $data);

                }

            }

        } catch (Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method requests_chat_history() @todo check and remove this function
     *
     * @uses used to get the messages list between user and provider
     *
     * @created Vidhya R
     *
     * @updated Vidhya R
     *
     * @param integer id, token
     *
     * @return json response 
     */

    public function requests_chat_history(Request $request) {

        $base_query = ChatMessage::where('user_id', $request->id)->where('request_id', $request->request_id);

        if($request->id) {

            ChatMessage::where('user_id', $request->id)
                ->where('request_id', $request->request_id)
                ->where('provider_id' , $request->provider_id)
                ->where('type' , 'pu')
                ->update(['delivered' => 1]);
        }

        $data = $base_query->get()->toArray();

        return $this->sendResponse($message = "", $code = "", $data);
    
    }

    /**
     * @method search_result()
     *
     * @uses used to get the search result based on the search key
     *
     * @created Vidhya R
     *
     * @updated Vidhya R
     *
     * @param
     *
     * @return json response 
     */

    public function search_result(Request $request) {

        $this->loginProvider->timezone ? date_default_timezone_set($this->loginProvider->timezone) : "";

        $today = date('Y-m-d');

        try {

            // @todo proper validation 
            $rules =[
                'search_type' => 'integer',
                'search_key' => '',
                'min_price'  => '',
                'max_price' => '',
                'on_off' => '',
                'from_date' => 'date|after:yesterday|bail',
                'to_date' => 'required_with:from_date|date|after:from_date|bail',
            ];
            $custom_errors = [
                'from_date.after' => tr('date_equal_or_greater_today'),
                'to_date.required_with' => tr('to_date_required'),
                'to_date.after' => tr('to_date_greater_from_date'),
            ];
            
            Helper::custom_validator($request->all(),$rules, $custom_errors);

            $host_base_query = Host::VerifedHostQuery()
                                ->leftJoin('host_details','host_details.host_id' ,'=' , 'hosts.id')
                                ->orderBy('hosts.created_at', 'desc');

            // Based on the search type pass the conditions

            // Based on the inputs return lists

            // Location based search

            if($request->service_location_id) {

                $host_base_query = $host_base_query->where('hosts.service_location_id', $request->service_location_id);

            }

            // Dates

            if($request->from_date && $request->to_date) {

                if(strtotime($request->from_date) > strtotime($request->to_date)) {

                    throw new Exception(api_error(213, $request->from_date), 213);

                }

                $from_date = date("Y-m-d",strtotime($request->from_date));

                $to_date = date("Y-m-d",strtotime($request->to_date));

                $available_host_ids = HostAvailability::where('status', NOTAVAILABLE)
                                        ->whereBetween('host_availabilities.available_date', [$from_date, $to_date])
                                        ->pluck('host_id');

                if($available_host_ids) {

                    $host_base_query = $host_base_query->whereNotIn('hosts.id', $available_host_ids);
                }
            
            }

            // Price based 

            if($request->price) {

                $pricings = json_decode($request->price);

                if($pricings) {

                    $min_price = $pricings->min_input ?: 10.00;

                    $max_price = $pricings->max_input ?: 1000000.00;

                    if($min_price && $max_price) {

                        $host_base_query = $host_base_query->whereBetween('hosts.per_day',[$min_price, $max_price]);
      
                    }
                }
            }

            // Host type

            if($request->space_type) {

                $space_types = explode(',', $request->space_type);

                $host_base_query = $host_base_query->whereIn('hosts.host_type', $space_types);

            }

            
            $host_ids = $host_base_query->skip($this->skip)->take($this->take)->pluck('hosts.id');

            $hosts = HostRepo::host_list_response($host_ids, $request->id);

            $hosts_data['title'] = tr('search_results');

            $hosts_data['description'] = "";

            $hosts_data['data'] = $hosts;

            $data = [];

            array_push($data, $hosts_data);

            return $this->sendResponse($message = "", $code = "", $data);
        
        } catch(Exception  $e) {
            
            return $this->sendError($e->getMessage(), $e->getCode());

        }

    }

    /**
     * @method filter_locations()
     *
     * @uses used get the related service location
     *
     * @created Vidhya R
     *
     * @updated Vidhya R
     *
     * @param
     *
     * @return json response 
     */

    public function filter_locations(Request $request) {

        try {

            $base_query = ServiceLocation::CommonResponse()->orderBy('service_locations.name', 'asc');

            if($request->location) {

                $base_query = $base_query->where('name', 'like', '%'.$request->location.'%');

            } else {

                $this->take = 6;
            }

            $service_locations = $base_query->skip($this->skip)->take($this->take)->get();

            return $this->sendResponse($message = "", $code = "", $service_locations);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }     

    }

    /**
     * @method bell_notifications()
     *
     * @uses list of notifications for user
     *
     * @created vithya R
     *
     * @updated vithya R
     *
     * @param integer $id
     *
     * @return JSON Response
     */

    public function bell_notifications(Request $request) {

        try {

            $bell_notifications = BellNotification::where('to_id', $request->id)->where('receiver', USER)
                            ->select('notification_type', 'booking_id', 'host_id', 'message', 'status as notification_status', 'from_id', 'to_id', 'receiver','redirection_type','updated_at')
                            ->orderBy('bell_notifications.id' , 'desc')
                            ->skip($this->skip)->take($this->take)
                            ->get();

            foreach ($bell_notifications as $key => $bell_notification_details) {

                $space_details = Host::find($bell_notification_details->host_id);

                $bell_notification_details->picture = $space_details->picture ?? asset('placeholder.jpg');

                $bell_notification_details->updated = $bell_notification_details->updated_at->diffForHumans();

                unset($bell_notification_details->from_id);

                unset($bell_notification_details->to_id);

                unset($bell_notification_details->updated_at);
            }

            return $this->sendResponse($message = "", $code = "", $bell_notifications);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());

        }   
    
    }

    /**
     * @method bell_notifications_update()
     *
     * @uses list of notifications for user
     *
     * @created vithya R
     *
     * @updated vithya R
     *
     * @param integer $id
     *
     * @return JSON Response
     */

    public function bell_notifications_update(Request $request) {

        try {

            DB::beginTransaction();

            $bell_notifications = BellNotification::where('to_id', $request->id)->where('receiver', USER)->update(['status' => BELL_NOTIFICATION_STATUS_READ]);

            DB::commit();

            return $this->sendResponse(api_success(204), 204, $data = []);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());

        } 
    
    }

    /**
     * @method bell_notifications_count()
     * 
     * @uses Get the notification count
     *
     * @created vithya R
     *
     * @updated vithya R
     *
     * @param object $request - As of no attribute
     * 
     * @return response of boolean
     */
    public function bell_notifications_count(Request $request) {

        // TODO
            
        $bell_notifications_count = BellNotification::where('status', BELL_NOTIFICATION_STATUS_UNREAD)->where('receiver', USER)->where('to_id', $request->id)->count();

        $data = [];

        $data['count'] = $bell_notifications_count;

        return $this->sendResponse($message = "", $code = "", $data);
    }

    /**
     * @method reviews_for_you()
     *
     * @uses used to get logged in user review
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param object $request
     *
     * @return response of details
     */
    public function reviews_for_you(Request $request) {

        try {

            $base_query = BookingProviderReview::where('booking_provider_reviews.user_id', $request->id)->CommonResponse()->orderBy('booking_provider_reviews.id' , 'desc');

            $reviews = $base_query->skip($this->skip)->take($this->take)->get();

            return $this->sendResponse($message = "", $code = "", $reviews);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method reviews_for_providers()
     *
     * @uses used to get loggedin user rated reviews
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param object $request
     *
     * @return response of details
     */
    public function reviews_for_providers(Request $request) {

        try {

            $base_query = BookingUserReview::where('booking_user_reviews.user_id', $request->id)->CommonResponse()->orderBy('booking_user_reviews.id' , 'desc');

            $reviews = $base_query->skip($this->skip)->take($this->take)->get();

            return $this->sendResponse($message = "", $code = "", $reviews);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method vehicles_index()
     *
     * @uses used to user vehicles
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param object $request
     *
     * @return response of details
     */
    public function vehicles_index(Request $request) {

        try {

            $vehicles = UserVehicle::CommonResponse()->where('user_id', $request->id)
            ->orderBy('user_vehicles.created_at', 'desc')->skip($this->skip)->take($this->take)->get();

            return $this->sendResponse($message = "", $success_code = "", $vehicles);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method vehicles_save()
     *
     * @uses used to update/create the user vehicle details
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param object $request
     *
     * @return response of details
     */
    public function vehicles_save(Request $request) {

        try {

            // Common validator for all steps

            $rules = [
                            'vehicle_type' => 'required',
                            'vehicle_number' => 'required',
                            'vehicle_brand' => 'required',
                            'vehicle_model' => 'required',
                            'user_vehicle_id' => 'exists:user_vehicles,id'
                ];

            Helper::custom_validator($request->all(),$rules);

            DB::beginTransaction();

            if($request->user_vehicle_id) {

                $vehicle_details = UserVehicle::where('id', $request->user_vehicle_id)->where('user_id', $request->id)->first();

            } else {

                $vehicle_details = new UserVehicle;

                $vehicle_details->user_id = $request->id;

            }

            $vehicle_details->vehicle_type = $request->vehicle_type;

            $vehicle_details->vehicle_number = $request->vehicle_number;

            $vehicle_details->vehicle_brand = $request->vehicle_brand;

            $vehicle_details->vehicle_model = $request->vehicle_model;

            $vehicle_details->save();

            DB::commit();

            $vehicle_details->user_vehicle_id = $vehicle_details->id;

            $message = "Vehicle Added";

            return $this->sendResponse($message, $success_code = "", $vehicle_details);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method vehicles_delete()
     *
     * @uses used to get the reviews based review_type = provider | Host @todo 
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param object $request
     *
     * @return response of details
     */
    public function vehicles_delete(Request $request) {

        try {

            // Common validator for all steps

            $rules = [
                        'user_vehicle_id' => 'exists:user_vehicles,id'
            ];

            Helper::custom_validator($request->all(),$rules);

            DB::beginTransaction();

            $vehicle_details = UserVehicle::where('id', $request->user_vehicle_id)->where('user_id', $request->id)->first();

            if(!$vehicle_details) {

                throw new Exception("Vehicle details not found", 101);
                
            }

            $vehicle_details->delete();

            DB::commit();

            $message = "Vehicle details deleted"; $code = 200;

            return $this->sendResponse($message, $code, $data = []);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method home_map()
     *
     * @uses used to get the reviews based review_type = provider | Host @todo 
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param object $request
     *
     * @return response of details
     */
    public function home_map(Request $request) {

        try {

            $today = date('Y-m-d H:i:s', strtotime("+30 minutes"));

            // Formate checkin and checkout dates // @todo change the variable name

            if($request->checkin) {

                $checkin = common_server_date($request->checkin, $this->timezone, 'Y-m-d H:i:s');

                $request->request->add(['checkin' => $checkin]);

            }

            if($request->checkout) {

                $checkout = common_server_date($request->checkout, $this->timezone, 'Y-m-d H:i:s');

                $request->request->add(['checkout' => $checkout]);

            }

            // @todo Check the min duration between checkin and checkout

            $rules = [
                // 'checkin' => 'nullable|bail|date|after:today',
                // 'checkout' => 'nullable|bail|required_if:checkin, |date|after:checkin',
                'latitude' => 'numeric',
                'longitude' => 'numeric|required_if:latitude,',
            ];

            Helper::custom_validator($request->all(),$rules);

            $base_query = Host::VerifedHostQuery()->where('hosts.total_spaces', '>', 0)->orderBy('hosts.updated_at', 'desc');

            if($request->latitude && $request->longitude) {

                $distance = Setting::get('search_radius', 100);

                $latitude = $request->latitude; $longitude = $request->longitude;

                $location_query = "SELECT hosts.id as host_id, 1.609344 * 3956 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) AS distance FROM hosts
                                        WHERE (1.609344 * 3956 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) <= $distance 
                                        ORDER BY distance";

                $location_hosts = DB::select(DB::raw($location_query));

                $location_host_ids = array_column($location_hosts, 'host_id');

                $base_query = $base_query->whereIn('hosts.id', $location_host_ids);

            }

            $l_host_ids = $base_query->pluck('hosts.id');

            // Get availability based hosts

            $hosts = Host::whereIn('id', $l_host_ids)->skip($this->skip)->take($this->take)->get();

            $host_ids = [];

            foreach ($hosts as $key => $host_details) {

                if($request->checkin && $request->checkout) {

                    if(BookingRepo::host_availability_based_hosts($request->checkin, $request->checkout, $host_details)) {

                        $host_ids[] = $host_details->id;

                    } else {

                        unset($hosts[$key]);
                    }

                } else {

                }
            }

            $hosts = [];

            if($host_ids) {
                
                $hosts = HostRepo::park_hosts_list_response($host_ids, $request->id, $request);

            }

            return $this->sendResponse($message = "", $success_code = "", $hosts);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method spaces_view()
     *
     * @uses  
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param object $request
     *
     * @return response of details
     */
    public function spaces_view(Request $request) {

        try {

            $host_details = Host::where('hosts.id', $request->space_id)->VerifedHostQuery()->UserParkFullResponse()->first();

            if(!$host_details) {

                throw new Exception(api_error(200), 200);
                
            }

            $host_details->total_bookings = Booking::where('host_id', $request->space_id)->count();

            $service_location_name = $host_details->serviceLocationDetails->name ?? '';

            $host_details->share_content = tr('share_content', Setting::get('site_name')).$service_location_name;

            $host_details->share_link = Setting::get('frontend_url')."search"; // We dont have seperate single view page for space in the frontend

            $host_details->wishlist_status = HostHelper::wishlist_status($request->space_id, $request->id);

            $host_details->per_hour_formatted = formatted_amount($host_details->per_hour);

            $tax_percentage = Setting::get('tax_percentage', 1)/100;

            $tax_price = $host_details->per_hour * $tax_percentage;

            $host_details->tax_price_formatted = formatted_amount($tax_price);

            $total = $host_details->per_hour + $tax_price;

            $host_details->total_formatted = formatted_amount($total);

            $host_details->booking_type_text = $host_details->is_automatic_booking ? tr('automatic_booking') : tr('manual_booking');
            
            $host_details->gallery = HostGallery::where('host_id', $host_details->space_id)->select('picture', 'caption')->skip(0)->take(3)->get();

            $host_details->amenities = get_amenities($host_details->amenities, $host_details->host_type);

            return $this->sendResponse($message = "", $success_code = "", $host_details);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method spaces_price_calculator()
     * 
     * @uses calculate the total amount for user requested inputs
     *
     * @created vithya R
     *
     * @updated vithya R
     *
     * @param
     * 
     * @return response of boolean
     */
    public function spaces_price_calculator(Request $request) {

        try {

            $today = date('Y-m-d H:i:s');

            // Formate checkin and checkout dates // @todo change the variable name

            $checkin = $request->checkin;

            Log::info("spaces_price_calculator + + +".print_r($request->all(), true));

            $rules = [
                'space_id' => 'required|exists:hosts,id,status,'.APPROVED,
                'checkin' => 'required|date',
                // 'checkout' => 'required_if:checkin,|date|after:checkin',
                'total_spaces' => 'min:1',
                'price_type' => 'required|in:'.PRICE_TYPE_DAY.','.PRICE_TYPE_MONTH.','.PRICE_TYPE_HOUR,
                'total_days' => ($request->price_type == PRICE_TYPE_DAY) ? 'required|numeric|min:1|max:30' : '',
                'total_hours' => ($request->price_type == PRICE_TYPE_HOUR) ? 'required|numeric|min:1|max:23' : '',
            ];

            $custom_errors = [
                'total_hours.required' => 'Total hours should not be greater that 23 Hours!!',
            ];

            Helper::custom_validator($request->all(),$rules);


            // check the host details

            $host = Host::where('id', $request->space_id)->VerifedHostQuery()->first();

            // $host_details = HostDetails::where('host_id', $request->host_id)->first();

            if(!$host) {

                throw new Exception(api_error(200), 200);
            }

            // Check the dates are available

            if(strtotime($request->checkin) > strtotime($request->checkout)) {
                
                // throw new Exception("The checkin date should be less than the checkout date", 101);

            }

            $date = new Carbon($request->checkin);

            if($request->price_type == PRICE_TYPE_DAY) {

                $checkout = $date->addDays($request->total_days);
                
            } else if($request->price_type == PRICE_TYPE_MONTH) {

                $total_months = 1;

                $checkout = $date->addMonths($total_months);

            } else if($request->price_type == PRICE_TYPE_HOUR) {
                
                $checkout = $date->addhour($request->total_hours);

            }

            $request->checkout = $checkout;

            $request->request->add(['checkin' => $checkin, 'checkout' => $checkout]);

            // $request->request->add(['checkin' => $checkin, 'checkout' => $checkout]);

            $date_difference = date_convertion($checkin, $checkout);

            // Check the host available on the selected dates

            // $check_host_availablity = HostHelper::check_host_availablity($request->checkin, $request->checkout, $request->host_id);

            // if($check_host_availablity == NO) {

                // throw new Exception("The host is not available on the selected dates", 101);  
            // }

            $data = new \stdClass;

            $data->space_id = $request->space_id;

            $data->checkin = common_date($request->checkin,"",'Y-m-d H:i:s');

            $data->checkout = common_date($request->checkout,"",'Y-m-d H:i:s');

            $days = $date_difference->days ?: 0;

            $hours = $date_difference->hours ?: 0;

            $months = $date_difference->months ?: 0;
            
            $days_price = $host->per_day * $days;

            $hours_price = $host->per_hour * $hours;

            $months_price = $host->per_month * $months;

            if($request->price_type == PRICE_TYPE_MONTH) {

                $actual_total = $months_price;

                $data->total_months = $months;

                $data->month_price = $months_price;

                $data->months_price = formatted_amount($months_price);

                $duration_text = $date_difference->months > 1 ? tr('months') : tr('month');

                $data->duration = $date_difference->months.' '.$duration_text;

            } else if($request->price_type == PRICE_TYPE_DAY) {

                $actual_total = $host->per_day * $request->total_days;

                $data->total_days = $days;

                $data->days_price = $days_price;

                $data->days_price = formatted_amount($days_price);

                $duration_text = $date_difference->days > 1 ? tr('days') : tr('day');

                $data->duration = $date_difference->days.' '.$duration_text;

            } else if($request->price_type == PRICE_TYPE_HOUR) {

                $actual_total = $days_price + $hours_price;

                $data->total_days = $days;

                $data->total_hours = $hours;

                $data->hours_price = $hours_price;

                $data->hours_price_formatted = formatted_amount($hours_price);

                $duration_text = $date_difference->hours > 1 ? tr('hours') : tr('hour');

                $data->duration = $date_difference->hours.' '.$duration_text;

            }

            $data->actual_price_formatted = formatted_amount($actual_total);

            $tax_percentage = Setting::get('tax_percentage', 1)/100;

            $tax_price = $actual_total * $tax_percentage;

            $data->tax_price_formatted = formatted_amount($tax_price);

            $total = $actual_total + $tax_price;

            $data->total = $total;

            $data->total_formatted = formatted_amount($total);

            Log::info("spaces_price_calculator data".print_r($data, true));

            return $this->sendResponse($message = "", $success_code = "", $data);

        } catch(Exception $e) {

            Log::info("spaces_price_calculator Exception".print_r($e->getMessage(), true));

            return $this->sendError($e->getMessage(), $e->getCode());

        }
    
    }

    /**
     * @method spaces_bookings_create()
     * 
     * @uses calculate the total amount for user requested inputs
     *
     * @created vithya R
     *
     * @updated vithya R
     *
     * @param
     * 
     * @return response of boolean
     */
    public function spaces_bookings_create(Request $request) {

        try {

            $today = date('Y-m-d H:i:s');

            $rules = [
                'space_id' => 'required|exists:hosts,id',
                'checkin' => 'required|date|after:'.$today.'|bail',
                'user_vehicle_id' => 'required|exists:user_vehicles,id',
                'payment_mode' => 'required',
                'price_type' => 'required|in:'.PRICE_TYPE_DAY.','.PRICE_TYPE_MONTH.','.PRICE_TYPE_HOUR,
                'total_days' => ($request->price_type == PRICE_TYPE_DAY) ? 'required|numeric|min:1|max:30' : '',
                'total_hours' => ($request->price_type == PRICE_TYPE_HOUR) ? 'required|numeric|min:1|max:23' : '',
            ];

            Helper::custom_validator($request->all(),$rules);

            // Step1: check the host details
            $host = Host::where('id', $request->space_id)->VerifedHostQuery()->first();

            if(!$host) {

                throw new Exception(Helper::error_message(200), 200);

            }

            // Step2: Check the payment mode and handle default card validation

            if($request->payment_mode == CARD) {

                // Check the user have default card

                $check_default_card = UserCard::where('user_id' , $request->id)->where('is_default', YES)->count();

                if($check_default_card == 0) {

                    throw new Exception(api_error(112), 112);
                    
                }

            }

            // Calculate checkout date based on price_type
            $date = new Carbon($request->checkin);

            // Based on price type- per day add total days from checkin date.
            // price type - per month add default 1 month
            if($request->price_type == PRICE_TYPE_DAY) {

                $checkout = $date->addDays($request->total_days);
                
            } else if($request->price_type == PRICE_TYPE_MONTH) {

                $total_months = 1;

                $checkout = $date->addMonths($total_months);

            } else {
                
                $checkout = $date->addhour($request->total_hours);
                
            }

            $checkin = $request->checkin ? common_server_date($request->checkin, $this->timezone ,'Y-m-d H:i:s') : "";

            $checkout = common_server_date($checkout, $this->timezone ,'Y-m-d H:i:s');

            $request->request->add(['checkin' => $checkin, 'checkout' => $checkout, 'timezone'=> $this->timezone]);

            // Step3: Check the user already booked same place with same vehicle

            $is_same_vehicle_booked = BookingRepo::bookings_check_same_vehicle_same_space($request);

            if($is_same_vehicle_booked == YES) {

                throw new Exception(api_error(503), 503);

            }

            // Check the host is available or not

            $is_host_available = BookingRepo::host_availability_based_hosts($request->checkin, $request->checkout, $host);

            if($is_host_available == false) {

                throw new Exception(api_error(502), 502);
                
            }

            $date_difference = date_convertion($request->checkin, $request->checkout);

            $booking_response = BookingRepo::bookings_save($request, $host, $date_difference)->getData();   
            
            if($booking_response->success == false) {

                throw new Exception($booking_response->error, 101);
            }

            $booking_details = Booking::where('bookings.id', $booking_response->data->booking_id)->first();

            $job_data['booking_details'] = $booking_details;

            $job_data['timezone'] = $this->timezone;

            $this->dispatch(new BookingsCreateJob($job_data));

            $response_array = json_decode(json_encode($booking_response), true);
            
            return response()->json($response_array, 200);
            
        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());

        }
    
    }

}
