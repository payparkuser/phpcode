<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Helpers\Helper, App\Helpers\HostHelper;

use App\Repositories\HostRepository as HostRepo;

use App\Repositories\BookingRepository as BookingRepo;

use App\Repositories\PushNotificationRepository as PushRepo;

use DB, Log, Hash, Validator, Exception, Setting;

use App\Provider, App\ProviderDetails, App\ProviderCard, App\ProviderSubscription, App\ProviderSubscriptionPayment;

use App\User, App\Wishlist;

use App\BellNotification;

use App\Lookups, App\StaticPage;

use App\Host, App\HostDetails, App\HostAvailability, App\HostGallery, App\HostInventory, App\HostAvailabilityList;

use App\Booking, App\BookingChat, App\BookingPayment, App\ChatMessage;

use App\BookingProviderReview, App\BookingUserReview;

use Carbon\Carbon;

use App\UserVehicle;

use App\Mail\ForgotPasswordMail;

use App\Jobs\BellNotificationJob, App\Jobs\SendEmailJob, App\Jobs\ProviderRatingJob, App\Jobs\ProviderBookingCancelJob;

use App\ProviderBillingInfo, App\ProviderDocument, App\Document;

use App\Jobs\ProviderBookingsCheckInJob, App\Jobs\ProviderBookingsCheckOutJob, App\Jobs\UserPaymentNotificationJob, App\Jobs\UserCheckoutNotificationJob, App\Jobs\BookingsApproveJob, App\Jobs\BookingsRejectJob;

use App\Repositories\AccountRepository as AccRepo;

class ProviderApiController extends Controller {

    protected $loginProvider, $skip, $take, $timezone, $currency, $push_notification_status;

    public function __construct(Request $request) {

        Log::info(url()->current());

        Log::info("Request DATA _ - _ - : _ - _ -".print_r($request->all(), true));

        $this->skip = $request->skip ?: 0;

        $this->take = $request->take ?: (Setting::get('admin_take_count') ?: TAKE_COUNT);

        $this->loginProvider = Provider::CommonResponse()->find($request->id);

        $this->currency = Setting::get('currency', '$');

        $this->push_notification_status = $this->loginProvider->push_notification_status ?? 0;

        $this->timezone = $this->loginProvider->timezone ?? "America/New_York";

        if($request->host_id) {

            $request->request->add(['space_id' => $request->host_id]);
            
        }

    }

    /**
     * @method register()
     *
     * @uses Registered provider can register through manual or social login
     * 
     * @created Vidhya R 
     *
     * @updated Vidhya R
     *
     * @param Form data
     *
     * @return Json response with provider details
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

            $allowed_social_login = ['facebook','google','apple'];

            if (in_array($request->login_by,$allowed_social_login)) {

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
                        'email' => 'unique:providers,email',
                    ];

                Helper::custom_validator($request->all(),$rules); 
            }

            $provider_details = Provider::where('email' , $request->email)->first();

            $send_email = DEFAULT_FALSE;

            // Creating the provider

            if(!$provider_details) {

                $provider_details = new Provider;

                register_mobile($request->device_type);

                $send_email = DEFAULT_TRUE;

                $provider_details->picture = asset('placeholder.jpg');

                $provider_details->registration_steps = 1;

            } else {

                if (in_array($provider_details->status , [PROVIDER_PENDING , PROVIDER_DECLINED])) {

                    throw new Exception(api_error(1000) , 1000);
                
                }

            }

            if($request->has('name')) {

                $provider_details->name = $request->name;

            }

            if($request->has('email')) {

                $provider_details->email = $request->email;

            }

            if($request->has('mobile')) {

                $provider_details->mobile = $request->mobile;

            }

            if($request->has('password')) {

                $provider_details->password = Hash::make($request->password ?: "123456");

            }

            $provider_details->gender = $request->gender ?: "male";

            $provider_details->payment_mode = COD;

            $provider_details->token = Helper::generate_token();

            $provider_details->token_expiry = Helper::generate_token_expiry();

            $check_device_exist = Provider::where('device_token', $request->device_token)->first();

            if($check_device_exist) {

                $check_device_exist->device_token = "";

                $check_device_exist->save();
            }

            $provider_details->device_token = $request->device_token ?: "";

            $provider_details->device_type = $request->device_type ?: DEVICE_WEB;

            $provider_details->login_by = $request->login_by ?: 'manual';

            $provider_details->social_unique_id = $request->social_unique_id ?: '';

            $provider_details->timezone = $request->timezone ?: 'America/New_York';

            // Upload picture

            if($request->login_by == "manual") {

                if($request->hasFile('picture')) {

                    $provider_details->picture = Helper::upload_file($request->file('picture') , PROFILE_PATH_PROVIDER);

                }

            } else {

                $provider_details->is_verified = PROVIDER_EMAIL_VERIFIED;

                $provider_details->picture = $request->picture ?: $provider_details->picture;

            }   

            if ($provider_details->save()) {

                // Send welcome email to the new provider:
                if($send_email) {

                    if ($provider_details->login_by == 'manual') {

                        $provider_details->password = $request->password;
                        
                        $email_data['subject'] = tr('provider_welcome_title').' '.Setting::get('site_name');

                        $email_data['page'] = "emails.providers.welcome";

                        $email_data['data'] = $provider_details;

                        $email_data['email'] = $provider_details->email;

                        $this->dispatch(new SendEmailJob($email_data));
                        
                    }

                }

                // DON"T ENABLE. FOR DOCUMENT UPLOAD, WE NO NEED TO HANDLE THIS

                if(in_array($provider_details->status , [PROVIDER_DECLINED , PROVIDER_PENDING])) {

                    // On Welcome, we need to send welcome message. DONT CHANGE THE BELOW ERROR

                    // !!!! NOTE: 1007 - Is only for message, don't change

                    // $error = $send_email == YES ? tr('provider_register_waiting_for_admin_approval') : api_error(1007);

                
                    // $response = ['success' => false , 'error' => $error , 'error_code' => 1000];

                    // DB::commit();

                    // return response()->json($response, 200);
               
                }

                if ($provider_details->is_verified == PROVIDER_EMAIL_VERIFIED) {

                    $data = Provider::CommonResponse()->find($provider_details->id);

                    $response_array = ['success' => true, 'message' => "Welcome ".$data->name, 'data' => $data];

                } else {

                    $response_array = ['success' => false, 'error' => api_error(1001), 'error_code' => 1001];

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

            $response_array = ['success'=>false, 'error'=> $e->getMessage(), 'error_code' => $e->getCode()];

            return response()->json($response_array, 200);

        }
   
    }

    /**
     * @method login()
     *
     * @uses Registered provider can login using their email & password
     * 
     * @created Vidhya R 
     *
     * @updated Vidhya R
     *
     * @param object $request - provider Email & Password
     *
     * @return Json response with provider details
     */
    public function login(Request $request) {

        try {

            DB::beginTransaction();

            $rules = 
                [
                    'device_token' => 'required',
                    'device_type' => 'required|in:'.DEVICE_ANDROID.','.DEVICE_IOS.','.DEVICE_WEB,
                    'login_by' => 'required|in:manual,facebook,google,apple',
                ];

           Helper::custom_validator($request->all(),$rules); 

            /*validate manual login fields*/

            $rules =
                [
                    'email' => 'required|email',
                    'password' => 'required',
                ];

            Helper::custom_validator($request->all(),$rules); 

            $provider_details = Provider::IsNotDeleted()->where('email', '=', $request->email)->first();

            // Check the provider details 

            if(!$provider_details) {
     
                throw new Exception(api_error(1006) , 1006);

            }                

            // check the provider approved status - FOR DOCUMENT UPLOAD, WE NO NEED TO HANDLE THIS

            if ($provider_details->status != PROVIDER_APPROVED) {

                // $error = api_error(1000);

                // throw new Exception($error , 1000);

            }

            // Check the provider is verified
            if (Setting::get('is_account_email_verification') == YES) {

                $is_email_verified = YES; // Initialize variables

                if (!$provider_details->is_verified) {
                    
                    Helper::check_email_verification("" , $provider_details->id, $error,PROVIDER);

                    $is_email_verified = NO;

                }

                if($is_email_verified == NO) {

                    $error = api_error(1001);

                    throw new Exception($error , 1001);

                }

            }

            // Check the password is matched
            if(!Hash::check($request->password, $provider_details->password)) {

                $error = api_error(102);

                throw new Exception($error , 102);

            }

            // Generate new tokens

            if(check_demo_login($provider_details->email, $provider_details->token)) {

                $provider_details->token = Helper::generate_token();

            }
            
            $provider_details->token_expiry = Helper::generate_token_expiry();
            
            // Save device details

            $check_device_exist = Provider::where('id', '!=',$provider_details->id)->where('device_token', $request->device_token)->update(['device_token' => ""]);


            $provider_details->device_token = $request->device_token ?: $provider_details->device_token;

            $provider_details->device_type = $request->device_type ?: $provider_details->device_type;

            $provider_details->login_by = $request->login_by ?: $provider_details->login_by;

            $provider_details->timezone = $request->timezone ?: $provider_details->timezone;

            $provider_details->save();

            DB::commit();

            $data = Provider::CommonResponse()->find($provider_details->id);

            return $this->sendResponse(api_success(101), 101, $data);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());

        }
    
    }

 
    /**
     * @method forgot_password()
     *
     * @uses If the provider forgot his/her password he can hange it over here
     *
     * @created Vidhya R 
     *
     * @updated Vidhya R
     *
     * @param object $request - Email id
     *
     * @return send mail to the valid provider
     */
    
    public function forgot_password(Request $request) {

        try {

            DB::beginTransaction();

            // Check email configuration and email notification enabled by admin

            if(Setting::get('is_email_notification') != 1 || envfile('MAIL_USERNAME') == "" || envfile('MAIL_PASSWORD') == "" ) {

                throw new Exception(api_error(106), 106);
                
            }
            
            $rules = 
                [
                    'email' => 'required|email|exists:providers,email',
                ];
            $custom_errors =
                [
                    'exists' => 'The :attribute doesn\'t exists',
                ];

            Helper::custom_validator($request->all(),$rules, $custom_errors); 
            
            $provider_details = Provider::where('email' , $request->email)->first();

            if(!$provider_details) {
     
                throw new Exception(api_error(1006) , 1006);

            }

            if($provider_details->login_by != "manual") {

                throw new Exception(api_error(116), 116);
                
            }

            // check email verification
            if($provider_details->is_verified == PROVIDER_EMAIL_NOT_VERIFIED) {

                throw new Exception(api_error(1007), 1007);

            }

            // Check the provider approve status

            if(in_array($provider_details->status , [PROVIDER_DECLINED , PROVIDER_PENDING])) {
                throw new Exception(api_error(1011), 1011);
            }

            $new_password = Helper::generate_password();

            $provider_details->password = Hash::make($new_password);
                    
            $email_data['subject'] =  Setting::get('site_name').' '.tr('forgot_email_title');

            $email_data['page'] = "emails.providers.forgot-password";

            $email_data['data'] = $provider_details;

            $email_data['password'] = $new_password;

            $email_data['email'] = $provider_details->email;

            $this->dispatch(new SendEmailJob($email_data));

            if(!$provider_details->save()) {

                throw new Exception(api_error(103), 103);

            }

            $response_array = ['success' => true , 'message' => api_success(102)];


            DB::commit();

            return response()->json($response_array, 200);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        }
    
    }

    /**
     * @method change_password()
     *
     * @uses To change the password of the provider
     *
     * @created Vidhya R 
     *
     * @updated Vidhya R
     *
     * @param object $request - Password & confirm Password
     *
     * @return json response of the provider
     */
    public function change_password(Request $request) {

        try {

            DB::beginTransaction();

            $rules = [
                    'password' => 'required|confirmed|min:6',
                    'old_password' => 'required|min:6',
                ];

            Helper::custom_validator($request->all(),$rules); 

            $provider_details = Provider::find($request->id);

            if(!$provider_details) {
         
                throw new Exception(api_error(1006) , 1006);

            }

            if($provider_details->login_by != "manual") {

                throw new Exception(api_error(119), 119);
                
            }

            if(Hash::check($request->old_password,$provider_details->password)) {

                $provider_details->password = Hash::make($request->password);
                
                if($provider_details->save()) {

                    $response_array = ['success' => true , 'message' => api_success(104)];
                
                } else {

                    throw new Exception(api_error(103), 103);
                    
                }

            } else {

                throw new Exception(api_error(108) , 108);
                
            }

            DB::commit();

            return response()->json($response_array,200);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());

        }

    }

    /** 
     * @method profile()
     *
     * @uses To display the provider details based on provider  id
     *
     * @created Vidhya R 
     *
     * @updated Vidhya R
     *
     * @param object $request - provider Id
     *
     * @return json response with provider details
     */

    public function profile(Request $request) {

        try {

            $provider_details = Provider::where('id' , $request->id)->FullResponse()->first();

            if (!$provider_details) { 

                $error = api_error(1006);

                throw new Exception($error , 1006);
                
            }

            $card_last_four_number = "";

            if ($provider_details->provider_card_id) {

                $card = ProviderCard::find($provider_details->provider_card_id);

                if ($card) {

                    $card_last_four_number = $card->last_four;

                }

            }

            $data = $provider_details->toArray();

            $data['card_last_four_number'] = $card_last_four_number;

            return $this->sendResponse("", "", $data);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());

        }
    
    }
 
    /**
     * @method update_profile()
     *
     * @uses To update the provider details
     *
     * @created Vidhya R 
     *
     * @updated Vidhya R
     *
     * @param objecct $request provider details
     *
     * @return json response with provider details
     */
    public function update_profile(Request $request) {

        try {

            DB::beginTransaction();
            
            $rules = 
                [
                    'name' => 'required|max:255',
                    'email' => 'email|unique:providers,email,'.$request->id.'|max:255',
                    'mobile' => 'digits_between:6,13',
                    'picture' => 'mimes:jpeg,bmp,png',
                    'gender' => 'in:male,female,others',
                    'device_token' => '',
                ];

            Helper::custom_validator($request->all(),$rules); 

            $provider_details = Provider::find($request->id);

            if(!$provider_details) {
         
                throw new Exception(api_error(1006) , 1006);

            }
                
            $provider_details->name = $request->name ? $request->name : $provider_details->name;

            
            if($request->has('email')) {

                $provider_details->email = $request->email;
            }

            $provider_details->mobile = $request->mobile ?: $provider_details->mobile;

            $provider_details->gender = $request->gender ?: $provider_details->gender;

            $provider_details->description = $request->description ?: '';

            $provider_details->timezone = $request->timezone ?: $provider_details->timezone;

            // Upload picture

            if ($request->hasFile('picture') != "") {

                // Delete the old pic

                Helper::delete_file($provider_details->picture, PROFILE_PATH_PROVIDER); 

                $provider_details->picture = Helper::upload_file($request->file('picture') , PROFILE_PATH_PROVIDER);

            }

            if ($provider_details->save()) {

                $data = Provider::CommonResponse()->find($provider_details->id);

                DB::commit();

                return $this->sendResponse(api_success(215), $code = 215, $data );

            } else {

                throw new Exception(api_error(103), 103);                    
            }

        } catch (Exception $e) {

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

            $provider_billing_info = ProviderBillingInfo::where('provider_id',$request->id)->first() ?? new ProviderBillingInfo;

            $provider_billing_info->provider_id = $request->id;

            $provider_billing_info->account_name = $request->account_name ?? "";

            $provider_billing_info->paypal_email = $request->paypal_email ?? "";

            $provider_billing_info->account_no = $request->account_no ?? "";

            $provider_billing_info->route_no = $request->route_no ?? "";

            if($provider_billing_info->save()) {

                DB::commit();

                $data = ProviderBillingInfo::find($provider_billing_info->id);

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

            $provider_billing_info = ProviderBillingInfo::where('provider_id',$request->id)->select('id as provider_billing_info_id' , 'account_name' , 'paypal_email' ,'account_no', 'route_no' )->first();

            $data = $provider_billing_info ?? [];

            return $this->sendResponse($message = "", $success_code = "", $data);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());

        }
    
    }
 

    /**
     * @method delete_account()
     * 
     * @uses Delete provider account based on provider id
     *
     * @created Vidhya R 
     *
     * @updated Vidhya R
     *
     * @param object $request - Password and provider id
     *
     * @return json with boolean output
     */

    public function delete_account(Request $request) {

        DB::beginTransaction();

        try {

            $request->request->add([ 
                'login_by' => $this->loginProvider ? $this->loginProvider->login_by : "manual",
            ]);

            $rules = 
                [
                    'password' => 'required_if:login_by,manual',
                ]; 
            $custom_errors = 
                [
                    'password.required_if' => 'The :attribute field is required.',
                ];

            Helper::custom_validator($request->all(),$rules, $custom_errors); 

            $provider_details = Provider::find($request->id);

            if(!$provider_details) {
     
                throw new Exception(api_error(1006) , 1006);

            }

            // The password is not required when the provider is login from social. If manual means the password is required

            if($provider_details->login_by == 'manual') {

                if(!Hash::check($request->password, $provider_details->password)) {

                    $is_delete_allow = NO ;

                    $error = api_error(108);
         
                    throw new Exception($error , 108);
                    
                }
            
            }

            $delete_response = AccRepo::provider_delete_response($provider_details);

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

    public function logout(Request $request) {

        // @later no logic for logout

        return $this->sendResponse(api_success(106), 106);

    }

    /**
     * @method cards_list()
     *
     * @uses get the provider payment mode and cards list
     *
     * @created Vidhya R
     *
     * @updated vithya R
     *
     * @param integer id
     * 
     * @return
     */

    public function cards_list(Request $request) {

        try {

            $provider_cards = ProviderCard::where('provider_id' , $request->id)->select('id as provider_card_id' , 'customer_id' , 'last_four' ,'card_name', 'card_token' , 'is_default' )->get();

            // $data = $provider_cards ? $provider_cards : []; 

            $card_payment_mode = $payment_modes = [];

            $card_payment_mode['name'] = "Card";

            $card_payment_mode['is_default'] = 1;

            array_push($payment_modes , $card_payment_mode);

            $data['payment_modes'] = $payment_modes;   

            $data['cards'] = $provider_cards ? $provider_cards : []; 

            $response_array = ['success' => true ,  'data' => $data];

            return response()->json($response_array , 200);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode() ?: 101);

        }
    
    }
    
    /**
     * @method cards_add()
     *
     * @uses Update the selected payment mode 
     *
     * @created Vidhya R
     *
     * @updated vithya R
     *
     * @param Form data
     * 
     * @return JSON Response
     */

    public function cards_add(Request $request) {

        try {

            DB::beginTransaction();

            if(Setting::get('stripe_secret_key')) {

                \Stripe\Stripe::setApiKey(Setting::get('stripe_secret_key'));

            } else {

                throw new Exception(api_error(107), 107);
            }
        
            $rules =[

                'card_token' => 'required',
            ];

            Helper::custom_validator($request->all(),$rules); 

            Log::info("INSIDE CARDS ADD");

            $provider_details = Provider::find($request->id);

            if(!$provider_details) {

                throw new Exception(api_error(1006), 1006);
                
            }

            // Get the key from settings table
            
            $customer = \Stripe\Customer::create([
                "email" => $provider_details->email,
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
                
                $card_details = new ProviderCard;

                $card_details->provider_id = $request->id;

                $card_details->customer_id = $customer_id;

                $card_details->last_four = $card_info_from_stripe->last4 ?? '';
                
                $card_details->card_token = $request->card_token ?? "NO-TOKEN";
                
                $card_details->card_name = $request->card_holder_name ?: $this->loginProvider->name;

                // Check is any default is available

                $check_card_details = ProviderCard::where('provider_id',$request->id)->count();

                $card_details->is_default = $check_card_details ? 0 : 1;

                if($card_details->save()) {

                    if($provider_details) {

                        $provider_details->provider_card_id = $check_card_details ? $provider_details->provider_card_id : $card_details->id;

                        $provider_details->save();
                    }

                    $data = ProviderCard::where('id' , $card_details->id)->select('id as provider_card_id' , 'customer_id' , 'last_four' ,'card_name', 'card_token' , 'is_default' )->first();

                    $response_array = ['success' => true , 'message' => api_success(105), 'data' => $data];

                } else {

                    throw new Exception(api_error(117), 117);
                    
                }
           
            } else {

                throw new Exception(api_error(117) , 117);
                
            }

            DB::commit();

            return response()->json($response_array , 200);

        } catch(Stripe_CardError $e) { // @todo error code check and handle error on one line

            Log::info("error1");

            $error1 = $e->getMessage();

            $response_array = ['success' => false , 'error' => $error1 ,'error_code' => 903];

            return response()->json($response_array , 200);

        } catch (Stripe_InvalidRequestError $e) {

            // Invalid parameters were supplied to Stripe's API

            Log::info("error2");

            $error2 = $e->getMessage();

            $response_array = ['success' => false , 'error' => $error2 ,'error_code' => 903];

            return response()->json($response_array , 200);

        } catch (Stripe_AuthenticationError $e) {

            Log::info("error3");

            // Authentication with Stripe's API failed
            $error3 = $e->getMessage();

            $response_array = ['success' => false , 'error' => $error3 ,'error_code' => 903];

            return response()->json($response_array , 200);

        } catch (Stripe_ApiConnectionError $e) {
            Log::info("error4");

            // Network communication with Stripe failed
            $error4 = $e->getMessage();

            $response_array = ['success' => false , 'error' => $error4 ,'error_code' => 903];

            return response()->json($response_array , 200);

        } catch (Stripe_Error $e) {
            Log::info("error5");

            // Display a very generic error to the provider, and maybe send
            // yourself an email
            $error5 = $e->getMessage();

            $response_array = ['success' => false , 'error' => $error5 ,'error_code' => 903];

            return response()->json($response_array , 200);

        } catch (\Stripe\StripeInvalidRequestError $e) {

            Log::info("error7");

            // Log::info(print_r($e,true));

            $response_array = ['success' => false , 'error' => api_error(903) ,'error_code' => 903];

            return response()->json($response_array , 200);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        }
   
    }

    /**
     * @method cards_delete()
     *
     * @uses delete the selected card
     *
     * @created Vidhya R
     *
     * @updated Vidhya R
     *
     * @param integer provider_card_id
     * 
     * @return JSON Response
     */

    public function cards_delete(Request $request) {

        // Log::info("cards_delete");

        DB::beginTransaction();

        try {
    
            $provider_card_id = $request->provider_card_id;

            $rules = 
                [
                    'provider_card_id' => 'required|integer|exists:provider_cards,id,provider_id,'.$request->id,
                ];
            $custom_errors = [
                    'exists' => 'The :attribute doesn\'t belong to provider:'.$this->loginProvider->name
                ];

            Helper::custom_validator($request->all(),$rules, $custom_errors); 


            $provider_details = Provider::find($request->id);

            ProviderCard::where('id',$provider_card_id)->delete();

            // @todo code optimize

            if($provider_details) {

                if($provider_details->payment_mode = CARD) {

                    // Check he added any other card

                    if($check_card = ProviderCard::where('provider_id' , $request->id)->first()) {

                        $check_card->is_default =  DEFAULT_TRUE;

                        $provider_details->provider_card_id = $check_card->id;

                        $check_card->save();

                    } else { 

                        $provider_details->payment_mode = COD;

                        $provider_details->provider_card_id = DEFAULT_FALSE;
                    
                    }
               
                }

                // Check the deleting card and default card are same

                if($provider_details->provider_card_id == $provider_card_id) {

                    $provider_details->provider_card_id = DEFAULT_FALSE;

                    $provider_details->save();
                }
                
                $provider_details->save();
            
            }

            $response_array = ['success' => true , 'message' => api_success(107) , 'code' => 107];

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
     * @created Vidhya R
     *
     * @updated Vidhya R
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
                    'provider_card_id' => 'required|integer|exists:provider_cards,id,provider_id,'.$request->id,
                ];
            $custom_errors = [

                    'exists' => 'The :attribute doesn\'t belong to provider:'.$this->loginProvider->name
                ];

           Helper::custom_validator($request->all(),$rules, $custom_errors); 

            $old_default_cards = ProviderCard::where('provider_id' , $request->id)->where('is_default', DEFAULT_TRUE)->update(['is_default' => DEFAULT_FALSE]);

            $card = ProviderCard::where('id' , $request->provider_card_id)->update(['is_default' => DEFAULT_TRUE]);

           //  $provider_details = $this->loginProvider;

            $provider_details = Provider::find($request->id);

            $provider_details->provider_card_id = $request->provider_card_id;

            $provider_details->save();

            $response_array = ['success' => true, 'message'=>api_success(108), 'code'=>108];
               
            DB::commit();

            return response()->json($response_array , 200);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }
    
    } 



    /**
     * @method notification_settings()
     *
     * To enable/disable notifications of email / push notification
     *
     * @created - vithya R
     *
     * @updated - vithya R
     *
     * @param - 
     *
     * @return response of details
     */
    public function notification_settings(Request $request) {

        try {

            DB::beginTransaction();

            $rules = 
                [
                    'status' => 'required|numeric',
                    'type'=>'required|in:'.EMAIL_NOTIFICATION.','.PUSH_NOTIFICATION
                ];

            Helper::custom_validator($request->all(),$rules); 

            $provider_details = Provider::find($request->id);

            if ($request->type == EMAIL_NOTIFICATION) {

                $provider_details->email_notification_status = $request->status;

            }

            if ($request->type == PUSH_NOTIFICATION) {

                $provider_details->push_notification_status = $request->status;

            }

            $provider_details->save();

            $message = $request->status ? api_success(206) : api_success(207);

            $data = ['id' => $provider_details->id , 'token' => $provider_details->token];

            $response_array = [
                'success' => true ,'message' => $message, 
                'email_notification_status' => (int) $provider_details->email_notification_status,  // Don't remove int (used ios)
                'push_notification_status' => (int) $provider_details->push_notification_status,    // Don't remove int (used ios)
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
     * @created vithya R
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
                'id' => 'required|exists:providers,id',
                'token' => 'required',

            ];

            Helper::custom_validator($request->all(),$rules); 

            // Update timezone details

            $provider_details = Provider::find($request->id);

            $message = "";

            if($provider_details && $request->timezone) {

                $provider_details->timezone = $request->timezone ?: $provider_details->timezone;

                $provider_details->save();

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

            $data['urls'] = $url_data;

            $notification_data['FCM_SENDER_ID'] = "";

            $notification_data['FCM_SERVER_KEY'] = $notification_data['FCM_API_KEY'] = "";

            $notification_data['FCM_PROTOCOL'] = "";

            $data['notification'] = $notification_data;

            $data['site_name'] = Setting::get('site_name');

            $data['site_logo'] = Setting::get('site_logo');

            $data['currency'] = Setting::get('currency');

            $data['provider_status'] =  $provider_details->status ?? PROVIDER_APPROVED;

            $data['is_document_verified'] =  $provider_details->is_document_verified ?? 0;

            return $this->sendResponse($message, $success_code = "", $data);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());

        }
   
    }

    /**
     * @method dashboard()
     *
     * @uses used to get the hosts
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param object $request
     *
     * @return response of details
     */
    public function dashboard(Request $request) {

        try {

            $data = new \stdClass;

            $data->currency = Setting::get('currency');

            $booking_payments = BookingPayment::where('provider_id', $request->id)->where('status', PAID);

            // Total Earnings

            $data->total_earnings = amount_decimel($booking_payments->sum('provider_amount'));

            $data->total_earnings_formatted = formatted_amount($data->total_earnings);

            // current_month_earnings

            $month = date('m');

            $data->current_month_earnings = amount_decimel($booking_payments->whereMonth('updated_at', '=', $month)->sum('provider_amount'));

            $data->current_month_earnings_formatted = formatted_amount($data->current_month_earnings);

            // Today Earnings

            $current_date = date('Y-m-d');

            $data->today_earnings = amount_decimel($booking_payments->whereDate('updated_at', '=', $current_date)->sum('provider_amount'));

            $data->today_earnings_formatted = formatted_amount($data->today_earnings);

            $bookings = Booking::where('provider_id', $request->id)->get();

            // Total Bookings

            $data->total_bookings = $bookings->count();

            $upcoming_status = [BOOKING_ONPROGRESS, BOOKING_DONE_BY_USER, BOOKING_CHECKIN];

            $data->total_upcoming_trips = Booking::where('provider_id', $request->id)->whereIn('bookings.status', $upcoming_status)->count();

            $completed_status = [BOOKING_COMPLETED, BOOKING_CHECKOUT];

            $data->total_completed_trips = Booking::where('provider_id', $request->id)->whereIn('bookings.status', $completed_status)->count();

            // Reviews

            $booking_user_reviews = BookingUserReview::where('provider_id', $request->id)->get();

            $data->total_reviews = $booking_user_reviews->count();

            $data->overall_rating = $booking_user_reviews->avg('ratings') ?: 0;

            // current month Highlights (Top Paid bookings)

            $current_month_highlights = BookingPayment::where('booking_payments.provider_id', $request->id)
                                            ->where('booking_payments.status', PAID)
                                            ->orderBy('booking_payments.provider_amount', 'desc')
                                            ->whereMonth('booking_payments.updated_at', '=', $month)
                                            ->skip($this->skip)->take(6)
                                            ->ProviderDashboardHighlights()->get();

            foreach ($current_month_highlights as $key => $current_month_highlight) {

                $current_month_highlight->provider_amount_formatted = formatted_amount($current_month_highlight->provider_amount);

                $current_month_highlight->paid_date = common_date($current_month_highlight->paid_date, $this->timezone, 'd M Y H:i:s');
            }

            $data->current_month_highlights = $current_month_highlights;

            $booking_ids = Booking::where('bookings.provider_id' , $request->id)
                            ->whereIn('bookings.status', $upcoming_status)
                            ->orderBy('bookings.id', 'desc')
                            ->skip($this->skip)->take(4)
                            ->pluck('bookings.id');

            $bookings = BookingRepo::provider_booking_list_response($booking_ids, $request->id, $this->timezone);
            $data->bookings = $bookings;

            // Last 10 days revenue

            $last_10_dates = generate_between_dates($start_date = date('Y-m-d'), $end_date = "", $format = "Y-m-d" ,$no_of_days = 9, $days_type = 'subtract');

            $last_x_days_earnings = [];

            foreach ($last_10_dates as $key => $date) {

                $last_x_days_earnings_data = [];

                $last_x_days_earnings_data['date'] = common_date($date, $this->timezone, 'd M Y');

                $total = BookingPayment::where('provider_id', $request->id)->where('status', PAID)->whereDate('updated_at', '=', $date)->sum('provider_amount');

                $last_x_days_earnings_data['total'] = amount_decimel($total);

                $last_x_days_earnings_data['total_formatted'] = formatted_amount($total);

                array_push($last_x_days_earnings, $last_x_days_earnings_data);

            }

            $data->last_x_days_earnings = $last_x_days_earnings;

            $response_array = ['success' => true , 'data' => $data];

            return response()->json($response_array , 200);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }


    /**
     * @method spaces_index()
     *
     * @uses used to get the hosts
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param object $request
     *
     * @return response of details
     */
    public function spaces_index(Request $request) {

        try {

            $hosts = Host::where('hosts.provider_id' , $request->id)

                        ->select('hosts.id as space_id', 'hosts.host_name as space_name', 'hosts.picture as space_picture', 'hosts.host_type as space_type', 'hosts.city as space_location', 'hosts.created_at', 'hosts.updated_at', 'hosts.is_admin_verified', 'hosts.status as provider_space_status', 'admin_status as admin_space_status', 'hosts.available_days', 'hosts.service_location_id','hosts.is_automatic_booking')
                        ->orderBy('hosts.updated_at' , 'desc')
                        ->skip($this->skip)
                        ->take($this->take)
                        ->get();

            foreach ($hosts as $key => $host_details) {

                $h_details = HostDetails::where('host_id',$host_details->host_id)->first();

                $host_additional_details_steps = 0;

                if($h_details) {
                    
                    $host_additional_details_steps = $h_details->step1 + $h_details->step2 + $h_details->step3 + $h_details->step4 + $h_details->step5 + $h_details->step6 + $h_details->step7 + $h_details->step8;
                }

                $host_details->space_location = $host_details->serviceLocationDetails->name ?? "";

                $host_details->is_completed = $host_additional_details_steps == 8 ? YES: NO;

                $host_details->complete_percentage = ($host_additional_details_steps/8) * 100;

                $host_details->host_picture = $host_details->host_picture ?: asset('host-placeholder.jpg');

                // To avoid send sending "service_location_details" object from relation

                $host_details->unsetRelation('serviceLocationDetails');

            }

            $response_array = ['success' => true , 'data' => $hosts];

            return response()->json($response_array , 200);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method spaces_upload_files() 
     *
     * @uses Draft the uploaded files
     *
     * @created Vithya R 
     *
     * @updated Vithya R
     *
     * @param
     *
     * @return json repsonse
     */  
    
    public function spaces_upload_files(Request $request) {

        try {

            DB::beginTransaction();

            // Validate the common and basic fields

            $rules = [
                'space_id' => 'required|exists:hosts,id,provider_id,'.$request->id,
                    'file' => 'required'
            ];

            $custom_errors = [
                'exists' => Helper::error_message(200)
            ];

            Helper::custom_validator($request->all(),$rules,$custom_errors);

            if($request->hasfile('file')) {

                $data = HostRepo::host_gallery_upload($request->file('file'), $request->space_id, $status = YES);

                DB::commit();

                $message = "Uploaded successfully"; // @todo proper message

                return $this->sendResponse($message, $code = "", $data);
            
            }

            throw new Exception("Please upload a file and try", 101);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method hosts_remove_files() 
     *
     * @uses Draft the uploaded files
     *
     * @created Vithya R 
     *
     * @updated Vithya R
     *
     * @param
     *
     * @return json repsonse
     */  
    
    public function spaces_remove_files(Request $request) {

        try {

            DB::beginTransaction();

            $rules = [
                'space_id' => 'required|exists:hosts,id,provider_id,'.$request->id,
                    'space_gallery_id' => 'required'
            ];

            $custom_errors = [
                'exists' => Helper::error_message(200)
            ];

            Helper::custom_validator($request->all(),$rules,$custom_errors);


            if(HostGallery::where('id', $request->space_gallery_id)->delete()) {

                DB::commit();

                $message = "The file removed";

                return $this->sendResponse($message, $code = "", $data = []);

            } else {

                throw new Exception("The action failed", 101);
            
            }

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method spaces_galleries() 
     *
     * @uses get the images of the selected host
     * 
     * @created Vithya R 
     *
     * @updated Vithya R
     *
     * @param integer host_id
     *
     * @return json repsonse
     */  
    
    public function spaces_galleries(Request $request) {

        try {

            DB::beginTransaction();

            // Validate the common and basic fields
            $rules = [
                'space_id' => 'required|exists:hosts,id,provider_id,'.$request->id,
            ];

            $custom_errors = [
                'exists' => Helper::error_message(200)
            ];

            Helper::custom_validator($request->all(),$rules,$custom_errors);

            $galleries = HostGallery::where('host_id', $request->space_id)->CommonResponse()->get();

            // For website purpose
            if($this->loginProvider->device_type == DEVICE_WEB) {

                $data['galleries'] = $galleries;

                $data['host_details'] = Host::select('id as space_id', 'host_name as space_name')->first();
            } else {
                $data = $galleries;
            }
            return $this->sendResponse($message = "", $code = "", $data);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method spaces_status()
     *
     * @uses used to update the status of the selected host
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param object $request
     *
     * @return response of details
     */
    public function spaces_status(Request $request) {

        try {

            DB::beginTransaction();

            $host_details = Host::where('id', $request->space_id)->where('provider_id', $request->id)->first();

            if(!$host_details) {

                throw new Exception(Helper::error_message(200), 200);                
            }

            $host_details->status = $host_details->status ? SPACE_OWNER_UNPUBLISHED : SPACE_OWNER_PUBLISHED;

            $host_details->save();

            DB::commit();

            $success_code = $host_details->status ? 208 : 209; $message = Helper::success_message($success_code);

            $data = ['space_id' => $request->space_id, 'provider_space_status' => $host_details->status];

            return $this->sendResponse($message, $success_code, $data);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());

        }

    }

    /**
     * @method spaces_delete()
     *
     * @uses used to update the status of the selected host
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param object $request
     *
     * @return response of details
     */
    public function spaces_delete(Request $request) {

        try {

            DB::beginTransaction();

            $host_details = Host::where('id', $request->space_id)->where('provider_id', $request->id)->first();

            if(!$host_details) {

                throw new Exception(Helper::error_message(200), 200);                
            }

            if($host_details->delete()) {

                DB::commit();

                $response_array = ['success' => true , 'message' => Helper::success_message(210)];

                return response()->json($response_array , 200);

            } else {

                throw new Exception(Helper::error_message(Helper::error_message(204)), 204);

            }

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());

        }

    }


    /**
     * @method spaces_availability()
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
    public function spaces_availability(Request $request) {

        try {

            $request->request->add(['loops' => (int) $request->loops]);

            $rules = [
                'space_id' => 'required|exists:hosts,id',
                'month' => 'required',
                'year' => 'required',
                'loops' => 'max:2|min:1',
            ];

            $custom_errors = [
                'required' => Helper::error_message(202),
                'exists.space_id' => Helper::error_message(200),
            ];

            Helper::custom_validator($request->all(),$rules,$custom_errors);

            $host_details = Host::where('hosts.id', $request->space_id)->first();

            if(!$host_details) {

                throw new Exception(Helper::error_message(200), 200);
                
            }

            $host_availabilities = HostAvailability::where('host_id', $request->space_id)->where('status', AVAILABLE)->get();

            $currency = Setting::get('currency') ?: "$";

            $data = [];

            $data_ranges = HostHelper::generate_date_range($request->year, $request->month, "+1 day", "Y-m-d", $request->loops ?: 2);

            foreach ($data_ranges as $key => $data_range_details) {

                foreach ($data_range_details->dates as $check => $date_details) {

                    $availability_data = new \stdClass;

                    $check_host_availablity = HostAvailability::where('host_id', $request->space_id)->where('available_date', $date_details)->first();

                    $availability_data->date = $date_details;

                    $availability_data->is_available = $availability_data->checkin_status = AVAILABLE;

                    $availability_data->is_blocked_booking = NO;

                    if($check_host_availablity) {

                        $availability_data->check = 1;

                        $availability_data->is_available = $check_host_availablity->status;

                        $availability_data->is_blocked_booking = $check_host_availablity->is_blocked_booking;

                        $availability_data->checkin_status = $check_host_availablity->checkin_status;

                    }
                
                    // The user can't book today date

                    if(strtotime($date_details) <= strtotime(date('Y-m-d'))) {
                        
                        $availability_data->is_available = NOTAVAILABLE;

                        $availability_data->is_blocked_booking = YES;

                    }

                    $availability_data->min_days = $host_details->min_days;

                    $availability_data->max_days = $host_details->max_days;

                    $price_details = new \stdClass;

                    $price_details->currency = $currency;

                    $price_details->price = $host_details->base_price;

                    $price_details->price_formatted = $currency." ".$host_details->base_price;

                    $availability_data->pricings = $price_details;

                    $now_data[] = $availability_data;

                }

                $first_month_data['title'] = $first_month_data['month'] = $data_range_details->month;

                $first_month_data['total_days'] = $data_range_details->total_days;

                $first_month_data['availability_data'] = $now_data;

                $data[] = $first_month_data;

            }

            $response_array = ['success' => true , 'data' => $data];

            return response()->json($response_array , 200);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method spaces_set_availability()
     *
     * @uses used to set availability for the selected host
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param object $request
     *
     * @return response of details
     */

    public function spaces_set_availability(Request $request) {

        try {

            $rules = [
                'space_id' => 'required|exists:hosts,id,provider_id,'.$request->id,
                'dates' => 'required'
            ];

            Helper::custom_validator($request->all(),$rules);

            DB::beginTransaction();

            $valid_dates = json_decode($request->dates);

            if(!$valid_dates) {

                throw new Exception("Invalid dates", 101);
                
            }

            // Based on the json save or remove the host availability

            // Check the dates are valid

            // $valid_dates = HostHelper::check_valid_dates($dates); // Not using now

            // check the dates are not exceed max limit @todo

            if($this->loginProvider->device_type == DEVICE_WEB) {
                
            }

            foreach ($valid_dates as $key => $requested_date) {

                $check_host_availablity = $host_availablity = HostAvailability::where('host_id', $request->space_id)->whereDate('available_date', $requested_date->date)->first();

                if(!$check_host_availablity) {

                    $host_availablity = new HostAvailability;

                }

                $host_availablity->provider_id = $request->id;

                $host_availablity->host_id = $request->space_id;

                $host_availablity->available_date = date('Y-m-d', strtotime($requested_date->date));

                $host_availablity->status = $host_availablity->checkin_status = $requested_date->is_blocked_booking  == YES ? DATE_NOTAVAILABLE : DATE_AVAILABLE;

                $host_availablity->is_blocked_booking = $requested_date->is_blocked_booking;

                $host_availablity->save();

            }

            DB::commit();

            return $this->sendResponse(Helper::success_message(211), $code = 211, $data = []);

        } catch(Exception $e) {

            DB::rollback();

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
                'booking_id' => 'required|exists:bookings,id,provider_id,'.$request->id,
            ];

            Helper::custom_validator($request->all(),$rules);

            $booking_details = Booking::where('bookings.provider_id', $request->id)->where('bookings.id', $request->booking_id)->ProviderBookingViewResponse()->first();

            if(!$booking_details) {

                throw new Exception(Helper::error_message(206), 206);
                
            }

            // No need to check for provider

            $host_details = Host::where('hosts.id', $booking_details->space_id)->first();

            if(!$host_details) {

                throw new Exception(Helper::error_message(200), 200);
                
            }

            $booking_details->host_unique_id = $host_details->unique_id;

            $booking_details->space_dimension = $host_details->dimension ?? '';

            $booking_details->total_spaces = $host_details->total_spaces ?? 0;
            
            $booking_details->booking_type_text = $booking_details->is_automatic_booking ? tr('automatic_booking') : tr('manual_booking');

            $service_location_name = $host_details->serviceLocationDetails->name ?? '';

            $booking_details->share_content = tr('share_content').$service_location_name;

            $booking_details->share_link = Setting::get('frontend_url')."history-details/".$booking_details->id;

            $booking_details->location = $host_details->serviceLocationDetails->name ?? "";

            $booking_details->total_formatted = formatted_amount($booking_details->total);

            $booking_details->status_text = booking_status($booking_details->status);

            
            $booking_details->checkin_time = common_date($booking_details->checkin, $this->timezone, 'h:i A');

            $booking_details->checkout_time = common_date($booking_details->checkout, $this->timezone, 'h:i A');

            $booking_details->checkin = common_date($booking_details->checkin, $this->timezone, 'd M Y');

            $booking_details->checkout = common_date($booking_details->checkout, $this->timezone, 'd M Y');

            $user_details = User::find($booking_details->user_id);

            $booking_details->user_name = $booking_details->user_picture = "";

            if($user_details) {

                $booking_details->user_name = $user_details->name;

                $booking_details->user_picture = $user_details->picture;
            }

            $booking_details->price_type_text = HostHelper::formatted_price_type($booking_details->price_type);
            
            $host_galleries = HostGallery::where('host_id', $host_details->id)->select('picture', 'caption')->get();

            $booking_details->gallery = $host_galleries;

            $booking_details->provider_details = Provider::where('id', $host_details->provider_id)->select('id as provider_id', 'name as provider_name', 'email', 'picture', 'mobile', 'description','created_at')->first();

            $booking_details->user_details = User::where('id', $booking_details->user_id)->OtherCommonResponse()->first() ?: [];

            $booking_payment_details = $booking_details->bookingPayments ?: new BookingPayment;

            $pricing_details = new \stdClass();

            $pricing_details->currency = $this->currency;

            $pricing_details->price_type = $booking_details->price_type ?? PRICE_TYPE_DAY;

            $pricing_details->price_type_text = HostHelper::formatted_price_type($booking_details->price_type);

            $pricing_details->per_hour_formatted = formatted_amount($host_details->per_hour);

            $pricing_details->per_hour = $host_details->per_hour ?: 0.00;

            $pricing_details->per_hour_formatted = formatted_amount($host_details->per_hour);


            $pricing_details->per_day = $host_details->per_day ?: 0.00;

            $pricing_details->per_day_formatted = formatted_amount($host_details->per_day);

            $pricing_details->days_amount = $host_details->per_day * $booking_details->total_days ?: 0.00;

            $pricing_details->days_amount_formatted = formatted_amount($pricing_details->days_amount);

            $pricing_details->per_month = $host_details->per_month ?: 0.00;

            $pricing_details->per_month_formatted = formatted_amount($host_details->per_month);
            
            $pricing_details->price_type_amount = $booking_details->price_type == PRICE_TYPE_MONTH ? $booking_payment_details->per_month : ($booking_details->price_type == PRICE_TYPE_DAY  ? $booking_payment_details->per_day : $booking_payment_details->per_hour);

            $pricing_details->price_type_amount_formatted = formatted_amount($pricing_details->price_type_amount);

            $pricing_details->tax_price = $booking_payment_details->tax_price ?: 0.00;

            $pricing_details->tax_price_formatted = formatted_amount($booking_payment_details->tax_price);

            if($booking_payment_details) {

                $pricing_details->payment_id = $booking_payment_details->payment_id ?: "";

                $pricing_details->payment_mode = $booking_payment_details->payment_mode ?: "CARD";

                $pricing_details->paid_date = common_date($booking_payment_details->paid_date ?: date('Y-m-d'));

                $pricing_details->paid_amount = $booking_payment_details->paid_amount ?: 0.00;

                $pricing_details->paid_amount_formatted = formatted_amount($booking_payment_details->paid_amount ?? 0.00);
                
                $pricing_details->provider_amount_formatted = formatted_amount($booking_payment_details->provider_amount ?? 0.00);

                $pricing_details->admin_amount_formatted = formatted_amount($booking_payment_details->admin_amount ?? 0.00);


                $pricing_details->paid_date = common_date($booking_payment_details->paid_date,$this->timezone); 

                $pricing_details->total_amount = $booking_payment_details->actual_total ?: 0.00;

                $pricing_details->total_amount_formatted = formatted_amount($booking_payment_details->actual_total ?? 0.00);
            }

            // Assign amenties to main data

            $booking_details->pricing_details = $pricing_details;

            $booking_details->status_text = booking_status($booking_details->status);

            // $booking_details->buttons = booking_btn_status($booking_details->status, $booking_details->id, PROVIDER);

            $booking_details->buttons = booking_btn_status($booking_details->status, $booking_details->id, PROVIDER, $booking_details->is_automatic_booking);

            $booking_details->vehicle_details = UserVehicle::CommonResponse()->where('user_vehicles.id', $booking_details->user_vehicle_id)->first() ?? '';
            
            $booking_details->cancelled_date = common_date($booking_payment_details->cancelled_date, $this->timezone) ?? ""; //Don't assign any default value

            $booking_details->cancelled_reason = $booking_details->cancelled_reason;

            $reviews = BookingProviderReview::where('booking_id', $request->booking_id)->select('review', 'ratings', 'id as booking_review_id')->first();

            $booking_details->reviews = $reviews ?: [];

            unset($booking_details->bookingPayments);

            $response_array =['success' => true, 'data' => $booking_details];

            return response()->json($response_array , 200);

        } catch(Exception $e) {

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
                'booking_id' => 'required|exists:bookings,id,provider_id,'.$request->id
            ];

            Helper::custom_validator($request->all(),$rules);

            $booking_details = Booking::where('bookings.id', $request->booking_id)->where('provider_id', $request->id)->first();

            if(!$booking_details) {

                throw new Exception(Helper::error_message(206), 206);
            }

            // check the required status to cancel the booking

            $cancelled_status = [BOOKING_CANCELLED_BY_USER, BOOKING_CANCELLED_BY_PROVIDER];

            if(in_array($booking_details->status, $cancelled_status)) {

                throw new Exception(Helper::error_message(209), 209);
                
            }

            // After checkin the user can't cancel the booking 

            if($booking_details->status == BOOKING_CHECKIN) {
                
                throw new Exception(Helper::error_message(217), 217);

            }

            DB::beginTransaction();

            // check the required status to cancel the booking

            $booking_details->status = BOOKING_CANCELLED_BY_PROVIDER;

            $booking_details->cancelled_reason = $request->cancelled_reason ?: "";

            $booking_details->cancelled_date = date('Y-m-d H:i:s');

            if($booking_details->save()) {

                // Reduce the provider amount from provider redeems
                BookingRepo::revert_provider_redeems($booking_details);

                // Add refund amount to the user
                BookingRepo::add_user_refund($booking_details);

                BookingRepo::bookings_cancel_revert_availability($booking_details);

                DB::commit();

                //Push Notification - Bookings cancelled by provider

                $job_data['booking_details'] = $booking_details;

                $job_data['timezone'] = $this->timezone;
                
                $this->dispatch(new ProviderBookingCancelJob($job_data));

                $message = Helper::success_message(212); $code = 212;

                $data['booking_id'] = $request->booking_id;

                DB::commit();

                return $this->sendResponse($message, $code, $data = []);

            } else {
                
                throw new Exception(Helper::error_message(207), 207);
                
            }

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method bookings_rating_report()
     *
     * @uses used to rating the booking
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

            $booking_details = Booking::where('provider_id', $request->id)->where('id', $request->booking_id)->first();

            if(!$booking_details) {

                throw new Exception(Helper::error_message(206), 206);
                
            }

            // Check the booking is eligible for review

            if(in_array($booking_details->status , [BOOKING_COMPLETED , BOOKING_CHECKOUT])) {

                // Check the provider already rated

                $check_provider_review = BookingProviderReview::where('booking_id', $request->booking_id)->count();

                if($check_provider_review) {

                    throw new Exception(Helper::error_message(218), 218);
                    
                }

                $review_details = new BookingProviderReview;

                $review_details->user_id = $booking_details->user_id;

                $review_details->provider_id = $booking_details->provider_id;

                $review_details->host_id = $booking_details->host_id;

                $review_details->booking_id = $booking_details->id;

                $review_details->ratings = $request->ratings ?: 0;

                $review_details->review = $request->review ?: "";

                $review_details->status = APPROVED;

                $review_details->save();

                DB::commit();

                $job_data['booking_details'] = $booking_details;

                $job_data['timezone'] = $this->timezone;

                $this->dispatch(new ProviderRatingJob($job_data));

                $data = ['booking_id' => $request->booking_id, 'booking_user_review_id' => $review_details->id];

                $message = Helper::success_message(216); $code = 216; 

                return $this->sendResponse($message, $code, $data);

            } else {

                throw new Exception(Helper::error_message(214), 214);
            }

        } catch(Exception $e) {

            DB::rollback();

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

            $base_query = Booking::where('bookings.provider_id' , $request->id)
                            ->orderBy('bookings.id' , 'desc')
                            ->skip($this->skip)->take($this->take)
                            ->whereIn('bookings.status', $upcoming_status);

            if($request->year) {

                $year = $request->year ?: date('Y');

                $base_query = $base_query->whereYear('bookings.checkin', '=', $year);

            }

            if($request->month) {

                $month = $request->month ?: date('m');

                $year = $request->year ?: date('Y');

                $base_query = $base_query->whereYear('bookings.checkin', '=', $year)->whereMonth('bookings.checkin', '=', $month);

            }

            $booking_ids = $base_query->pluck('bookings.id');

            $bookings = BookingRepo::provider_booking_list_response($booking_ids, $request->id, $this->timezone);

            return $this->sendResponse("", "", $bookings);

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

            $base_query = Booking::where('bookings.provider_id' , $request->id)->orderBy('bookings.id' , 'desc');

            // For website temp purpose. V2.0 - Remove this condition @todo

            if($this->loginProvider->device_type != DEVICE_WEB) {

                $history_status = [BOOKING_CANCELLED_BY_USER, BOOKING_CANCELLED_BY_PROVIDER, BOOKING_COMPLETED, BOOKING_REFUND_INITIATED, BOOKING_CHECKOUT];

                $base_query = $base_query->whereIn('bookings.status', $history_status);

            }

            $booking_ids = $base_query->skip($this->skip)->take($this->take)
                            ->pluck('bookings.id');

            $bookings = $booking_ids ? BookingRepo::provider_booking_list_response($booking_ids, $request->id, $this->timezone) : [];

            return $this->sendResponse("", "", $bookings);

        } catch(Exception  $e) {
            
            return $this->sendError($e->getMessage(), $e->getCode());

        }

    }

    /**
     * @method transactions_history()
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
    public function transactions_history(Request $request) {

        try {

            // @todo only paid records

            $base_query = BookingPayment::where('provider_id', $request->id)->where('status', PAID)->orderBy('paid_date', 'desc');

            $payments = $base_query->skip($this->skip)->take($this->take)->get();

            $data = [];

            foreach ($payments as $key => $payment_details) {

                $transaction_data['booking_payment_id'] = $payment_details->id;

                $transaction_data['payment_id'] = $payment_details->payment_id;

                $transaction_data['paid_date'] = common_date($payment_details->paid_date, $this->timezone, 'd M Y');

                $transaction_data['payment_mode'] = $payment_details->payment_mode;

                $transaction_data['message'] = "Booking Payment";

                $transaction_data['paid_amount'] = $payment_details->paid_amount;

                $transaction_data['paid_amount_formatted'] = formatted_amount($payment_details->paid_amount);

                $transaction_data['provider_amount'] = $payment_details->provider_amount;

                $transaction_data['provider_amount_formatted'] = formatted_amount($payment_details->provider_amount);

                array_push($data, $transaction_data);

            }

            return $this->sendResponse($message = "", $code = "", $data);

        } catch(Exception $e) {

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

            $chat_messages = ChatMessage::where('provider_id' , $request->id)
                                ->select('booking_id','host_id as space_id', 'user_id', 'type', 'type as chat_type','updated_at', 'message')
                                ->groupBy('user_id')
                                ->orderBy('updated_at' , 'desc')
                                ->skip($this->skip)
                                ->take($this->take)
                                ->get();

            foreach ($chat_messages as $key => $chat_message_details) {

                $user_details = User::find($chat_message_details->user_id);

                $chat_message_details->user_name = $chat_message_details->user_picture = "";

                $chat_message_details->updated = $chat_message_details->updated_at->diffForHumans();

                if($user_details) {

                    $chat_message_details->user_name = $user_details->name;

                    $chat_message_details->user_picture = $user_details->picture;
                }
                
            }

            return $this->sendResponse("", "", $chat_messages);

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

            // @todo proper validation

            $rules = [
                'space_id' => 'required', 
                'user_id' => 'required'
            ];

            Helper::custom_validator($request->all(),$rules);

            $base_query = ChatMessage::select('booking_id', 'host_id as space_id', 'provider_id', 'user_id', 'type', 'type as chat_type','updated_at', 'message');

            if($request->booking_id) {

                $base_query = $base_query->where('chat_messages.booking_id' , $request->booking_id);

            }

            // if($request->space_id) {

            //     $base_query = $base_query->where('chat_messages.host_id' , $request->space_id);

            // }

            // if($request->user_id) {

            //     $base_query = $base_query->where('chat_messages.user_id' , $request->user_id);

            // }

            $chat_messages = $base_query->orderBy('chat_messages.updated_at' , 'desc')
                    ->skip($this->skip)->take($this->take)  
                    ->get();

            foreach ($chat_messages as $key => $chat_message_details) {

                $user_details = User::find($chat_message_details->user_id);

                $chat_message_details->user_name = $chat_message_details->user_picture = "";

                $chat_message_details->updated = $chat_message_details->updated_at->diffForHumans();

                if($user_details) {

                    $chat_message_details->user_name = $user_details->username;

                    $chat_message_details->user_picture = $user_details->picture;
                }
                
            }

            return $this->sendResponse($message = "", $code = "", $chat_messages);

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

            $bell_notifications = BellNotification::where('to_id', $request->id)->where('receiver', 'provider')
                            ->select('notification_type', 'booking_id', 'host_id as space_id', 'message', 'status as notification_status', 'redirection_type', 'from_id', 'to_id', 'receiver','updated_at')
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

            return $this->sendResponse($message = "", $success_code = "", $bell_notifications);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());

        }   
    
    }

    /**
     * @method bell_notifications_update()
     *
     * @uses list of notifications for provider
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

            $bell_notifications = BellNotification::where('to_id', $request->id)->where('receiver', PROVIDER)->update(['status' => BELL_NOTIFICATION_STATUS_READ]);

            DB::commit();

            $response_array = ['success' => true, 'message' => Helper::success_message(204), 'code' => 204];

            return response()->json($response_array, 200);


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
            
        $bell_notifications_count = BellNotification::where('status', BELL_NOTIFICATION_STATUS_UNREAD)->where('receiver', PROVIDER)->where('to_id', $request->id)->count();

        $response_array = ['success' => true, 'count' => $bell_notifications_count];

        return response()->json($response_array);

    }


    /**
     * @method subscriptions() 
     *
     * @uses used to get the list of subscriptions
     *
     * @created Vithya R 
     *
     * @updated Vithya R
     *
     * @param
     *
     * @return json repsonse
     */     

    public function subscriptions(Request $request) {

        try {

            $base_query = ProviderSubscription::where('provider_subscriptions.status', APPROVED)->CommonResponse();

            $provider_subscription_payment = ProviderSubscriptionPayment::where('provider_id', $request->id)->where('subscription_amount','=', 0)->first();

            if ($provider_subscription_payment) {

                $base_query = $base_query->where('amount','!=', 0);
            }

            $provider_subscriptions = $base_query->orderBy('updated_at', 'desc')->skip($this->skip)->take($this->take)->get();

            foreach ($provider_subscriptions as $key => $subscription_details) {

                $subscription_details->amount_formatted = formatted_amount($subscription_details->amount);

                $subscription_details->plan_text = plan_text($subscription_details->plan, $subscription_details->plan_type);
            }
           
            $response_array = ['success' => true, 'data' => $provider_subscriptions];

            return response()->json($response_array,200);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());

        }

    }

    /**
     * @method subscriptions_payment_by_stripe() 
     *
     * @uses used to deduct amount for selected subscription
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param
     *
     * @return json repsonse
     */     

    public function subscriptions_payment_by_stripe(Request $request) {

        try {

            $rules = [
                'provider_subscription_id' => 'required|exists:provider_subscriptions,id',
            ];

            $custom_errors = [
                'provider_subscription_id' => Helper::error_message(203)
            ];

            Helper::custom_validator($request->all(),$rules,$custom_errors);

            DB::beginTransaction();

            // Check Subscriptions

            $subscription_details = ProviderSubscription::where('id', $request->provider_subscription_id)->where('status', APPROVED)->first();

            if (!$subscription_details) {

                throw new Exception(Helper::error_message(203), 203);
            }

            if($subscription_details->amount <= 0) {

                $payment_id = "PAID-ZERO-".$subscription_details->id."-".$request->id;

                $total = $amount = 0; $paid_status = YES;

                goto zero_payment;

            }

            // Check provider card details

            $provider_card_details = ProviderCard::where('provider_id', $request->id)->where('is_default',YES)->first();

            if (!$provider_card_details) {

                throw new Exception(Helper::error_message(111), 111);
            }

            $customer_id = $provider_card_details->customer_id;

            // Check stripe configuration
        
            $stripe_secret_key = Setting::get('stripe_secret_key');

            if(!$stripe_secret_key) {

                throw new Exception(Helper::error_message(107), 107);

            } 

            \Stripe\Stripe::setApiKey($stripe_secret_key);

            $total = intval(round($subscription_details->amount * 100));

            $currency_code = Setting::get('currency_code') ?: "USD";

            $stripe = new \Stripe\StripeClient(Setting::get('stripe_secret_key'));

            $update = $stripe->paymentMethods->attach(
                $provider_card_details->card_token,
                ['customer' => $customer_id]
            );

            $charge_array = [
                'amount' => $total,
                "currency" => Setting::get('currency_code', 'USD'),
                "customer" => $customer_id,
                "payment_method" => $provider_card_details->card_token,
                'off_session' => true,
                'confirm' => true,
            ];

            $stripe_payment_response = \Stripe\PaymentIntent::create($charge_array);
            
            $payment_id = $stripe_payment_response->id?? 'CARD-'.rand();

            $amount = $stripe_payment_response->amount/100 ?? $total;

            $paid_status = $stripe_payment_response->paid ?? true;

            // Used goto function, dont remove the below line.

            zero_payment:

            $previous_payment = ProviderSubscriptionPayment::where('provider_id' , $request->id)->where('status', PAID)->orderBy('id', 'desc')->first();

            $provider_subscription_payment = new ProviderSubscriptionPayment;

            $provider_subscription_payment->expiry_date = date('Y-m-d H:i:s',strtotime("+{$subscription_details->plan} months"));

            if ($previous_payment) {

                if (strtotime($previous_payment->expiry_date) >= strtotime(date('Y-m-d H:i:s'))) {

                    $provider_subscription_payment->expiry_date = date('Y-m-d H:i:s', strtotime("+{$subscription_details->plan} months", strtotime($previous_payment->expiry_date)));

                }

            }

            $provider_subscription_payment->provider_id = $request->id;

            $provider_subscription_payment->provider_subscription_id = $request->provider_subscription_id;

            $provider_subscription_payment->payment_id = $payment_id;

            $provider_subscription_payment->subscription_amount = $total ?: 0.00;

            $provider_subscription_payment->subscribed_by = PROVIDER;

            $provider_subscription_payment->paid_amount = $amount ?: 0.00;

            $provider_subscription_payment->paid_date = date('Y-m-d H:i:s');

            $provider_subscription_payment->status = PAID;

            $provider_subscription_payment->is_current_subscription = YES;

            // Update previous current subscriptions as zero

            ProviderSubscriptionPayment::where('provider_id', $request->id)->update(['is_current_subscription' => NO]);

            if ($provider_subscription_payment->save()) {

                $provider_details = Provider::find($request->id);

                $provider_details->provider_type = YES;

                if ($provider_details->save()) {

                } else {

                    throw new Exception(Helper::error_message(204));
                }

            } else {

                throw new Exception(Helper::error_message(204));
                
            }

            DB::commit();

            $data = ['provider_subscription_id' => $provider_subscription_payment->id, 'payment_id' => $provider_subscription_payment->payment_id, 'paid_amount' => $amount, 'paid_amount_formatted' => formatted_amount($amount)];

            return $this->sendResponse($message = Helper::success_message(205), 205, $data);

        } catch(Stripe_CardError | Stripe_InvalidRequestError | Stripe_AuthenticationError | Stripe_ApiConnectionError | Stripe_Error $e) {

            $error_message = $e->getMessage();

            $error_code = $e->getCode();

            if($provider_subscription_payment) {

                $provider_subscription_payment->status = UNPAID;

                $provider_subscription_payment->cancelled_reason = $error_message;

                $provider_subscription_payment->is_cancelled = YES;

                $provider_subscription_payment->save();

            }

            DB::commit();

            $response_array = ['success'=>false, 'error'=> $error_message , 'error_code' => 204];

            return response()->json($response_array);

        } catch(Exception $e) {

            // Something else happened, completely unrelated to Stripe

            DB::rollback();

            $error_message = $e->getMessage();

            if(isset($provider_subscription_payment)) {

                $provider_subscription_payment->status = UNPAID;

                $provider_subscription_payment->cancelled_reason = $error_message;
                
                $provider_subscription_payment->is_cancelled = YES;

                $provider_subscription_payment->save();

            }

            return $this->sendError($e->getMessage(), $e->getCode());

        }

    }

    /**
     * @method subscriptions_history() 
     *
     * @uses List of subscription payments
     *
     * @created Vithya R 
     *
     * @updated Vithya R
     *
     * @param
     *
     * @return json repsonse
     */     

    public function subscriptions_history(Request $request) {

        try {

            $base_query = ProviderSubscriptionPayment::where('provider_id', $request->id)->select('provider_subscription_payments.id as provider_subscription_payment_id', 'provider_subscription_payments.*');

            $provider_subscription_payments = $base_query->orderBy('expiry_date', 'desc')->skip($this->skip)->take($this->take)->get();

            foreach ($provider_subscription_payments as $key => $payment_details) {

                // Subscription details

                $payment_details->title = $payment_details->description = $payment_details->plan_text = "";

                $subscription_details = ProviderSubscription::find($payment_details->provider_subscription_id);

                $payment_details->paid_amount_formatted = formatted_amount($payment_details->paid_amount);
                
                $payment_details->subscription_amount_formatted = formatted_amount($payment_details->subscription_amount);

                if($subscription_details) {

                    $payment_details->plan_text = plan_text($subscription_details->plan, $subscription_details->plan_type);

                    $payment_details->title = $subscription_details->title ?: "";

                    $payment_details->description = $subscription_details->description ?: "";
                }

                $payment_details->expiry_date = common_date($payment_details->expiry_date, $this->timezone, 'd M Y');

                $payment_details->paid_date = $payment_details->paid_date ? common_date($payment_details->paid_date, $this->timezone, 'd M Y'): "";

                $payment_details->status_text = subscription_status($payment_details->status);

                unset($payment_details->id);
            }

            $response_array = ['success' => true, 'data' => $provider_subscription_payments];

            return response()->json($response_array,200);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());

        }

    }

    /**
     * @method reviews_for_you()
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
    public function reviews_for_you(Request $request) {

        try {

            $base_query = BookingUserReview::where('booking_user_reviews.provider_id', $request->id)->CommonResponse();

            $reviews = $base_query->orderBy('booking_user_reviews.id' , 'desc')->skip($this->skip)->take($this->take)->get();

            return $this->sendResponse($message = "", $success_code = "", $reviews);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method reviews_for_users()
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
    public function reviews_for_users(Request $request) {

        try {

            $base_query = BookingProviderReview::where('booking_provider_reviews.provider_id', $request->id)->CommonResponse();

            $reviews = $base_query->orderBy('booking_provider_reviews.id' , 'desc')->skip($this->skip)->take($this->take)->get();

            return $this->sendResponse($message = "", $success_code = "", $reviews);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method spaces_configurations() 
     *
     * @uses save or update the host details
     *
     * @created Vithya R 
     *
     * @updated Vithya R
     *
     * @param
     *
     * @return json repsonse
     */  
    public function spaces_configurations(Request $request) {

        $host_types = [SPACE_TYPE_DRIVEWAY,SPACE_TYPE_GARAGE, SPACE_TYPE_CAR_PARK];

        $data = [];

        if($request->space_id) {

            $host = Host::where('provider_id', $request->id)->where('id', $request->space_id)->first();
        }

        foreach ($host_types as $key => $value) {

            $type_data = [];

            $type_data['space_type'] = $value;

            $lookup_key = $value == SPACE_TYPE_CAR_PARK ?: SPACE_TYPE_DRIVEWAY;

            $lookups = Lookups::Approved()->areAmenities()->where('type', $value)->select('id as amenity_lookup_id','key','value')->get();

            foreach ($lookups as $key => $lookup_details) {

                $lookup_details->is_selected = NO;

                if($request->space_id) {

                    if($host && $host->host_type == $value) {

                        $check_details = Host::where('id', $request->space_id)
                        ->whereRaw("find_in_set('".$lookup_details->amenity_lookup_id."',amenities)")
                        ->count();

                        $lookup_details->is_selected = $check_details ? YES: NO;
                    }

                }
            }

            $type_data['features'] = $lookups;

            array_push($data, $type_data);
       
        }

        return $this->sendResponse($message = "", $code = "", $data);

    }

    /**
     * @method spaces_save() 
     *
     * @uses save or update the host details
     *
     * @created Vithya R 
     *
     * @updated Vithya R
     *
     * @param
     *
     * @return json repsonse
     */  
    public function spaces_save(Request $request) {

        try {

            if($request->step == "pricings") {

                $rules = [
                    'per_hour' => 'required|min:0',
                    'per_day' => 'required|min:0',
                    'per_month' => 'min:0',
                    'space_id' => 'exists:hosts,id',
                ];

            } else {

                if(!$request->space_id) {

                    $provider_details = Provider::find($request->id);
                    
                    if(in_array($provider_details->status , [PROVIDER_DECLINED , PROVIDER_PENDING])) {

                        throw new Exception(Helper::error_message(1011), 1011);
                        
                    }
                }

                $rules = [
                    'total_spaces' => 'required',
                    'total_spaces' => 'required|min:1',
                    'access_note' => 'required',
                    'access_method' => 'required',
                    'space_owner_type' => 'required',
                    'full_address' => 'required',
                    'street_details' => 'required',
                    'country' => 'required',
                    'city' => 'required',
                    'state' => 'required',
                    'latitude' => 'required',
                    'longitude' => 'required',
                    'zipcode' => 'required',
                    'service_location_id' => 'required|exists:service_locations,id',
                    'security_code' => '',
                    'space_id' => 'nullable|exists:hosts,id',
                    'width_of_space' => '',
                    'height_of_space' => '',
                    'length_of_space' => '',
                    'is_automatic_booking' => 'nullable|numeric'
                ];

            }

            Helper::custom_validator($request->all(),$rules);


            DB::beginTransaction();

            if(!$request->space_id) {

                // Check the provider type is subscribed

                $provider_type = Helper::check_provider_type($this->loginProvider);

                if($provider_type == PROVIDER_TYPE_NORMAL) {

                    throw new Exception(Helper::error_message(1009), 1009);
                    
                }

            }

            $host_response = HostRepo::spaces_save($request);

            if($host_response['success'] == false) {

                throw new Exception($host_response['error'], $host_response['error_code']);
                
            }

            if($request->mobile || $request->name) {

                $provider_details = Provider::find($request->id);

                $provider_details->mobile = $request->mobile ?: "";

                $provider_details->name = $request->name ?: "";

                $provider_details->save();
            }

            $host = $host_response['host'];

            $host_details = $host_response ['host_details']; // Not Used

            DB::commit();

            // send response

            $message = $request->step == "pricings" ? tr('host_pricing_updated') : ($request->space_id ? tr('host_updated_success') : tr('host_created_success'));

            $success_code = 200;

            $data = Host::select('id as space_id', 'host_name as space_name')->where('hosts.id', $host->id)->first();

            return $this->sendResponse($message, $success_code, $data);

        } catch(Exception $e) {

            DB::rollback();

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

            $rules = [
                'space_id' => 'required|exists:hosts,id'
            ];

            Helper::custom_validator($request->all(),$rules);

            $host_details = Host::where('hosts.id', $request->space_id)->ProviderParkFullResponse()->first();

            if(!$host_details) {

                throw new Exception(Helper::error_message(200), 200);
                
            }

            $host_details->service_location_name = $host_details->serviceLocationDetails->name ?? "";

            $host_details->total_bookings = Booking::where('host_id', $request->space_id)->count();

            $host_details->per_hour_formatted = formatted_amount($host_details->per_hour);

            $host_details->per_day_formatted = formatted_amount($host_details->per_day);

            $host_details->per_month_formatted = formatted_amount($host_details->per_month);

            $host_details->galleries = HostGallery::where('host_id', $host_details->host_id)->select('picture', 'caption')->skip(0)->take(3)->get();

            $host_details->name = $this->loginProvider->name ?? "";

            $host_details->mobile = $this->loginProvider->mobile ?? "";

            $host_details->amenities_text = HostHelper::amenties_data($host_details);

            return $this->sendResponse($message = "", $success_code = "", $host_details);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method spaces_availability_list 
     *
     * @uses create availability list
     *
     * @created Vithya R 
     *
     * @updated Vithya R
     *
     * @param
     *
     * @return json repsonse
     */  
    
    public function spaces_availability_list(Request $request) {

        Log::info("spaces");

        try {

            $rules = [
                'space_id' => 'required|exists:hosts,id'
            ];

            Helper::custom_validator($request->all(),$rules);

            $host = Host::where('hosts.id', $request->space_id)->ProviderParkFullResponse()->first();

            if(!$host) {

                throw new Exception(Helper::error_message(200), 200);
                
            }

            $lists = HostAvailabilityList::where('host_id', $request->space_id)->CommonResponse()->skip($this->skip)->take($this->take)->get();

            foreach ($lists as $key => $details) {

                $type = $details->type == SPACE_AVAIL_ADD_SPACE ? "+": "-";

                $details->spaces_text = $type." "."$details->spaces";

                $details->available_spaces = available_spaces($host->total_spaces, $details->type, $details->spaces);


                $details->from_time = common_date($details->from_date, $this->timezone, 'h:i A');

                $details->from_date = common_date($details->from_date, $this->timezone, 'd M Y');


                $details->to_time = common_date($details->to_date, $this->timezone, 'h:i A');

                $details->to_date = common_date($details->to_date, $this->timezone, 'd M Y');

            }

            return $this->sendResponse($message = "", $code = "", $lists);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());

        }
    
    }
    
    /**
     * @method space_availability_list_save 
     *
     * @uses create availability list
     *
     * @created Vithya R 
     *
     * @updated Vithya R
     *
     * @param
     *
     * @return json repsonse
     */  
    
    public function space_availability_list_save(Request $request) {

        try {

            // Validate the inputs

            $today = date('Y-m-d H:i:s');

            // Formate checkin and checkout dates

            $from_date = common_date($request->from_date, "" ,'Y-m-d H:i:s');

            $to_date = common_date($request->to_date, "" ,'Y-m-d H:i:s');

            $request->request->add(['from_date' => $from_date, 'to_date' => $to_date]);

            $rules = [
                'available_days' => '',
                'from_date' => 'nullable|date',
                'to_date' => 'required_if:from_date,|date|after:from_date',
                'spaces' => 'required|min:0',
                'type' => 'required' // Add or Remove
            ];

            Helper::custom_validator($request->all(),$rules);


            DB::beginTransaction();

            $host = Host::find($request->space_id);

            if(!$host) {

                throw new Exception(Helper::error_message(200), 200);
                
            }

            // create a new list

            $host_availablity = new HostAvailabilityList;

            $host_availablity->host_id = $request->space_id;

            $host_availablity->provider_id = $request->id;

            $host_availablity->from_date = $request->from_date;

            $host_availablity->to_date = $request->to_date;

            $host_availablity->type = $request->type;

            $host_availablity->spaces = $request->spaces;

            $host_availablity->save();

            // check the available_days has value 

            if($request->has('available_days')) {

                $host->available_days = $request->available_days;

                $host->save();

            }

            HostRepo::host_availablity_list_update($request, $host);

            DB::commit();

            return $this->sendResponse(Helper::success_message(211), $code = 211, $data = []);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     *
     * @method space_availability_list_delete()
     *
     * @uses delete the selected list
     *
     * @created vidhya R
     *
     * @updated vidhya R
     *
     * @param integer host_id
     *
     * @return json object
     * 
     */

    public function space_availability_list_delete(Request $request) {

        try {

            $rules = [
                'host_availability_id' => 'required|exists:host_availability_lists,id'
            ];

            Helper::custom_validator($request->all(),$rules);

            $host_availability_list = HostAvailabilityList::find($request->host_availability_id);

            if(!$host_availability_list) {

                throw new Exception(Helper::error_message(501), 501);
                
            }

            $host = Host::where('hosts.id', $host_availability_list->host_id)->first();

            if(!$host) {

                throw new Exception(Helper::error_message(200), 200);
                
            }

            DB::beginTransaction();

            $data['space_id'] = $host->host_id;

            $data['space_availability_id'] = $request->host_availability_id;

            if($host_availability_list->delete()) {

                DB::commit();

                $message = Helper::success_message(500); $code = 500;

                return $this->sendResponse($message, $code, $data);
            }

            throw new Exception(Helper::error_message(500), 500);
            
        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());

        }
    
    }

    /**
     * @method users_view()
     *
     * @uses used to get the user details
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param object $request
     *
     * @return response of details
     */
    public function users_view(Request $request) {

        try {

            $user_details = User::where('users.status', USER_APPROVED)
                                    ->where('users.is_verified', USER_EMAIL_VERIFIED)
                                    ->where('users.id', $request->user_id)
                                    ->OtherCommonResponse()
                                    ->first();

            if(!$user_details) {

                throw new Exception(Helper::error_message(215), 215);                
            }

            $reviews = BookingProviderReview::where('user_id', $request->user_id)->get();

            $user_details->total_reviews = $reviews->count();

            $reviews = BookingUserReview::where('booking_user_reviews.provider_id', $request->id)->CommonResponse()->orderBy('booking_user_reviews.id' , 'desc')->skip($this->skip)->take($this->take)->get();


            $user_details->reviews = $reviews;

            // Other Questions

            return $this->sendResponse($message = "", $success_code = "", $user_details);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }


    /**
     * @method documents_index()
     *
     * @uses Used to get the list of documents
     *
     * @created Vidhya R 
     *
     * @updated Vidhya R
     *
     * @param object $request
     *
     * @return JSON Response
     */

    public function documents_index(Request $request) {

        $base_query = Document::where('status', APPROVED)->CommonResponse();

        $type = $request->type ?: DOCUMENT_TYPE_OTHERS;

        $documents = $base_query->where('type', $type)->orderBy('documents.id', 'asc')->get();

        foreach($documents as $document_details) {

            $document_url = ""; $provider_document_id = 0;

            $provider_document_details = ProviderDocument::where('provider_id', $request->id)->where('document_id', $document_details->document_id)->first();

            if($provider_document_details) {

                $document_url = $provider_document_details->document_url ?: "";

                $provider_document_id = $provider_document_details->id;

            }

            $document_details->document_url = $document_url;

            $document_details->provider_document_id = $provider_document_id;
        }
        
        return $this->sendResponse($message = "", $code = "", $documents);
    
    }

    /**
     * @method documents_upload()
     *
     * @uses update the provider uploaded document
     *
     * @created Vidhya R 
     *
     * @updated Vidhya R
     *
     * @param object $request
     *
     * @return JSON Response
     */
    public function documents_upload(Request $request) {

        try {

            $rules = [
                'document_id' => 'nullable|required_if:type, |exists:documents,id,status,'.APPROVED,
                'document_url' => 'required|mimes:png,jpeg,jpg'
            ];

            $custom_errors = [
                'document_id' => Helper::error_message(232)
            ];

            Helper::custom_validator($request->all(),$rules,$custom_errors);

            $data = [];

            if($request->type == DOCUMENT_TYPE_IDENTITY) {

                $provider_details = Provider::find($request->id);

                $provider_details->identity_verification_file = Helper::upload_file($request->document_url, FILE_PATH_PROVIDER_DOCUMENT);

                $provider_details->save();

                $data = ['identity_verification_file' => $provider_details->identity_verification_file]; 

            } else {

                $document_details = Document::find($request->document_id);

                if(!$document_details) {

                    throw new Exception(Helper::error_message(232), 232);
                    
                }

                // Check the provider document record 

                $provider_document = ProviderDocument::where('document_id', $request->document_id)->where('provider_id', $request->id)->first();

                if($provider_document) {

                    Helper::delete_file($provider_document->document_url, FILE_PATH_PROVIDER_DOCUMENT);

                } else {

                    $provider_document = new ProviderDocument;

                    $provider_document->provider_id = $request->id;

                    $provider_document->document_id = $request->document_id;

                }

                $provider_document->document_url = Helper::upload_file($request->file('document_url'), FILE_PATH_PROVIDER_DOCUMENT);

                $provider_document->save();


                $data = ['provider_document_id' => $provider_document->id, 'document_url' => $provider_document->document_url];

            }

            DB::commit();

            $message = Helper::success_message(225);

            return $this->sendResponse($message, $code = 225, $data);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        }
    
    }

    /**
     * @method documents_delete()
     *
     * @uses remove the uploaded document
     *
     * @created Vidhya R 
     *
     * @updated Vidhya R
     *
     * @param object $request
     *
     * @return JSON Response
     */

    public function documents_delete(Request $request) {

        try {

            $rules = [
                'provider_document_id' => 'required|exists:provider_documents,id'
            ];

            $custom_errors = [
                'provider_document_id' => Helper::error_message(232)
            ];

            Helper::custom_validator($request->all(),$rules,$custom_errors);

            $provider_document = ProviderDocument::where('id', $request->provider_document_id)->where('provider_id', $request->id)->first();

            if(!$provider_document) {

                throw new Exception(Helper::error_message(233), 233);
                
            }
           
            $provider_document->delete();

            DB::commit();

            $message = Helper::success_message(226);

            $data = ['provider_document_id' => $request->provider_document_id];

            return $this->sendResponse($message, $code = 226, $data);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        }
    
    }

    /**
     * @method bookings_approve()
     *
     * @uses Used to approve the booking
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param object $request
     *
     * @return response of details
     */
    public function bookings_approve(Request $request) {

        try {

            $rules = [
                'booking_id' => 'required|exists:bookings,id,provider_id,'.$request->id
            ];

            Helper::custom_validator($request->all(),$rules);


            $booking_details = Booking::where('bookings.id', $request->booking_id)->where('provider_id', $request->id)->first();

            if(!$booking_details) {

                throw new Exception(Helper::error_message(206), 206);

            }

            $host_details = Host::find($booking_details->host_id);

            // check the required status to cancel the booking

            $cancelled_status = [BOOKING_CANCELLED_BY_USER, BOOKING_CANCELLED_BY_PROVIDER];

            if(in_array($booking_details->status, $cancelled_status)) {

                throw new Exception(Helper::error_message(209), 209);
                
            }

            // Check already approved 

            if($booking_details->status == BOOKING_APPROVED_BY_PROVIDER) {
                
                throw new Exception(tr('bookings_approved_by_provider'), 220);

            }

            DB::beginTransaction();

            $booking_details->status = BOOKING_APPROVED_BY_PROVIDER;
            
            if($booking_details->save()) {

                // Deduct the payment here start

                $booking_payment_response = BookingRepo::bookings_payment_by_stripe($host_details, $booking_details)->getData();

                if($booking_payment_response->success == true) {
            
                    $booking_details->status = BOOKING_APPROVED_BY_PROVIDER;

                    $booking_details->save();

                } else {

                    $booking_details->status = BOOKING_WAITING_FOR_PAYMENT;

                    $booking_details->save();

                }
                
                // Deduct the payment here end

                DB::commit();

                $job_data['booking_details'] = $booking_details;

                $job_data['timezone'] = $this->timezone;

                $this->dispatch(new BookingsApproveJob($job_data));

                $message = Helper::success_message(502); $code = 502;

                $data['booking_id'] = $request->booking_id;

                return $this->sendResponse($message, $code, $response_data['booking_id'] = $request->booking_id);

            } else {
                
                throw new Exception(Helper::error_message(207), 207);
                
            }

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method bookings_reject()
     *
     * @uses Used to reject the booking
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param object $request
     *
     * @return response of details
     */
    public function bookings_reject(Request $request) {

        try {

            $rules = [
                'booking_id' => 'required|exists:bookings,id,provider_id,'.$request->id
            ];

            Helper::custom_validator($request->all(),$rules);

            $booking_details = Booking::where('bookings.id', $request->booking_id)->where('provider_id', $request->id)->first();

            if(!$booking_details) {

                throw new Exception(Helper::error_message(206), 206);
            }

            // Check already rejected 

            if($booking_details->status == BOOKING_CANCELLED_BY_PROVIDER) {
                
                throw new Exception(Helper::error_message(505), 505);

            }

            // check the required status to cancel the booking

            $cancelled_status = [BOOKING_CANCELLED_BY_USER, BOOKING_CANCELLED_BY_PROVIDER];

            if(in_array($booking_details->status, $cancelled_status)) {

                throw new Exception(Helper::error_message(209), 209);
                
            }

            DB::beginTransaction();

            $booking_details->status = BOOKING_CANCELLED_BY_PROVIDER;

            $booking_details->cancelled_reason = $request->cancelled_reason ?: "";

            $booking_details->cancelled_date = date('Y-m-d H:i:s');

            if($booking_details->save()) {

                BookingRepo::bookings_cancel_revert_availability($booking_details);
                
                // Reduce the provider amount from provider redeems
                BookingRepo::revert_provider_redeems($booking_details);

                // Add refund amount to the user
                BookingRepo::add_user_refund($booking_details);
                
                DB::commit();

                $job_data['booking_details'] = $booking_details;

                $job_data['timezone'] = $this->timezone;

                $this->dispatch(new BookingsRejectJob($job_data));

                $message = Helper::success_message(503); $code = 503;

                $response_data['booking_id'] = $request->booking_id;

                return $this->sendResponse($message, $code, $response_data);

            } else {
                
                throw new Exception(Helper::error_message(207), 207);
                
            }


        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method bookings_checkin()
     *
     * @uses used to update the checkout status of booking
     *
     * @created Bhawya
     *
     * @updated Bhawya
     *
     * @param object $request
     *
     * @return response of details
     */

    public function bookings_checkin(Request $request){

        try {

            $rules = [
                'booking_id' => 'required|exists:bookings,id,provider_id,'.$request->id,
                'checkin_verification_code' => 'required'
            ];

            Helper::custom_validator($request->all(),$rules);
            
            $booking_details = Booking::where('bookings.id', $request->booking_id)->where('provider_id', $request->id)->first();

            if(!$booking_details) {

                throw new Exception(Helper::error_message(206), 206);

            }


            if($booking_details->checkin_verification_code != $request->checkin_verification_code) {

                throw new Exception(Helper::error_message(235), 235);

            }

            // Check the booking is already checked-in

            if($booking_details->status == BOOKING_CHECKIN) {

                throw new Exception(Helper::error_message(222), 222);
                
            }

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
            
            $check_time = (strtotime($booking_details->checkin) - time())/60;
            // CheckTime Validation- Before 10 mins only Provider can checkin
            if($check_time > 10) {

                throw new Exception(api_error(236), 236);

            }

            DB::beginTransaction();

            $booking_details->status = BOOKING_CHECKIN;

            $booking_details->checkin = $actual_checkin->format('Y-m-d H:i:s');

            if($booking_details->save()) {


                DB::commit();

                // Notification to user for bookings checkin by provider

                $job_data['booking_details'] = $booking_details;

                $job_data['timezone'] = $this->timezone;

                $this->dispatch(new ProviderBookingsCheckInJob($job_data));

                $data = ['booking_id' => $booking_details->id, 'checkin' => common_date($booking_details->checkin)];

                return $this->sendResponse(Helper::success_message(218), 218, $data);

            } else {

                throw new Exception(Helper::error_message(221), 221);
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
     * @created Bhawya N
     *
     * @updated Bhawya N
     *
     * @param object $request
     *
     * @return response of details
     */

    public function bookings_checkout(Request $request){

        try {

            $rules = [
                'booking_id' => 'required|exists:bookings,id,provider_id,'.$request->id
            ];

            Helper::custom_validator($request->all(),$rules);
            
            $booking_details = Booking::where('bookings.id', $request->booking_id)->first();

            if(!$booking_details) {

                throw new Exception(Helper::error_message(206), 206);
            }

            // check the booking is already checked-out

            if($booking_details->status == BOOKING_CHECKOUT) {

                throw new Exception(Helper::error_message(225), 225);
                
            }

            // check the booking is eligible for checkout

            if($booking_details->status != BOOKING_CHECKIN) {

                throw new Exception(Helper::error_message(223), 223);
                
            }

            DB::beginTransaction();

            // $check_out = common_server_date(date("Y-m-d H:i:s"), $this->timezone, 'Y-m-d H:i:s');

            $current_time = new \DateTime("now", new \DateTimeZone("UTC"));

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

                    $job_data['timezone'] = $this->timezone;

                    $this->dispatch(new UserPaymentNotificationJob($job_data));
                }
            
            }
            
            $booking_details->status = BOOKING_CHECKOUT;

            $booking_details->checkout = $check_out;

            if($booking_details->save()) {

                DB::commit();                

                // notification to user for bookings checkout by provider

                $job_data['booking_details'] = $booking_details;

                $job_data['timezone'] = $this->timezone;

                $this->dispatch(new ProviderBookingsCheckOutJob($job_data));


                $data = ['booking_id' => $booking_details->id, 'checkout' => common_date($booking_details->checkout), 'status_text' => booking_status($booking_details->status)];

                return $this->sendResponse(Helper::success_message(219), 219, $data);

            } else {


                throw new Exception(Helper::error_message(224), 224);
            }

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method checkout_notification()
     *
     * @uses used to send user notification for checkout 
     *
     * @created Bhawya N
     *
     * @updated Bhawya N
     *
     * @param object $request
     *
     * @return response of details
     */

    public function checkout_notification(Request $request){

        try {

            $rules = [
                'booking_id' => 'required|exists:bookings,id,provider_id,'.$request->id
            ];

            Helper::custom_validator($request->all(),$rules);

            $booking_details = Booking::where('bookings.id', $request->booking_id)->first();

            if(!$booking_details) {

                throw new Exception(Helper::error_message(206), 206);
            }

            // check the booking is already checked-out

            if($booking_details->status == BOOKING_CHECKOUT) {

                throw new Exception(Helper::error_message(225), 225);
                
            }

            // check the booking is eligible for checkout
            if($booking_details->status != BOOKING_CHECKIN) {

                throw new Exception(Helper::error_message(223), 223);
                
            }

            $job_data['booking_details'] = $booking_details;

            $job_data['timezone'] = $this->timezone;

            $this->dispatch(new UserCheckoutNotificationJob($job_data));

            return $this->sendResponse(Helper::success_message(504), 504, $data =[]);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method spaces_available_days_update 
     *
     * @uses create availability list
     *
     * @created Vithya R 
     *
     * @updated Vithya R
     *
     * @param
     *
     * @return json repsonse
     */  
    
    public function spaces_available_days_update(Request $request) {

        try {

            $rules = [
                'available_days' => 'required',
                'space_id' => 'required|exists:hosts,id'
            ];

            Helper::custom_validator($request->all(),$rules);

            DB::beginTransaction();

            $host = Host::find($request->space_id);

            if(!$host) {

                throw new Exception(Helper::error_message(200), 200);
                
            }

            // check the available_days has value 

            if($request->has('available_days')) {

                $host->available_days = $request->available_days;

                $host->save();

            }

            DB::commit();

            return $this->sendResponse(Helper::success_message(211), $code = 211, $data = []);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

}
