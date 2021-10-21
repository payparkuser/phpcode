<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Log, Validator, Exception, DB, Hash, PDF, Setting;

use Carbon\Carbon;

use App\User, App\Provider, App\Admin, App\Host;

use App\StaticPage, App\Lookups;

use App\ChatMessage;

use App\ServiceLocation;

use App\Booking, App\BookingPayment;

use App\Helpers\Helper;

use App\Jobs\SendEmailJob;

use App\Jobs\BookingsCheckInJob, App\Jobs\BookingsCheckOutJob;

use Auth;

class ApplicationController extends Controller {

	/**
     * @method static_pages()
     *
     * @uses used to display the static page for mobile devices
     *
     * @created Vidhya R
     *
     * @updated Vidhya R
     *
     * @param string $page_type 
     *
     * @return reidrect to the view page
     */

    public function static_pages($page_type = 'terms') {

        $page_details = StaticPage::where('type' , $page_type)->first();

        return view('static_pages.view')->with('page_details', $page_details);

    }  


    /**
     * @method static_pages_api()
     *
     * @uses used to get the pages
     *
     * @created Vidhya R 
     *
     * @updated Vidhya R
     *
     * @param - 
     *
     * @return JSON Response
     */

    public function static_pages_api(Request $request) {

        if($request->page_type) {

            $static_page = StaticPage::where('type' , $request->page_type)
                                ->where('status' , APPROVED)
                                ->select('id as page_id' , 'title' , 'description','type as page_type', 'status' , 'created_at' , 'updated_at')
                                ->first();

            $response_array = ['success' => true , 'data' => $static_page];

        } else {

            $static_pages = StaticPage::where('status' , APPROVED)->orderBy('id' , 'asc')
                                ->select('id as page_id' , 'title' , 'description','type as page_type', 'status' , 'created_at' , 'updated_at')
                                ->orderBy('title', 'asc')
                                ->get();

            $response_array = ['success' => true , 'data' => $static_pages ? $static_pages->toArray(): []];

        }
        
        return response()->json($response_array , 200);

    }

    /**
     * @method static_pages_api()
     *
     * @uses used to get the pages
     *
     * @created Vidhya R 
     *
     * @updated Vidhya R
     *
     * @param - 
     *
     * @return JSON Response
     */

    public function static_pages_web(Request $request) {

        $static_page = StaticPage::where('unique_id' , $request->unique_id)
                            ->where('status' , APPROVED)
                            ->select('id as page_id' , 'title' , 'description','type as page_type', 'status' , 'created_at' , 'updated_at')
                            ->first();

        $response_array = ['success' => true , 'data' => $static_page];

        

        return response()->json($response_array , 200);

    }

    /**
     * @method chat_messages_save()
     * 
     * @uses - To save the chat message.
     *
     * @created vidhya R
     *
     * @updated vidhya R
     * 
     * @param 
     *
     * @return No return response.
     *
     */

    public function chat_messages_save(Request $request) {

        try {

            Log::info("message_save".print_r($request->all() , true));

            $rules = [
                'user_id' => 'required|integer',
                'provider_id' => 'required|integer',
                'type' => 'required|in:up,pu',
                'message' => 'required',
                'host_id' => 'integer',
                'booking_id' => 'integer'
            ];

            Helper::custom_validator($request->all(),$rules);

            $type = 'chat';
            
            $message = $request->message;

            $chat_data = ['provider_id' => "$request->provider_id" , 'user_id' => "$request->user_id" ];

            //up - user to provider and pu -provider to user

            if($request->type == 'up') {

                $push_send_status = 1;

                // Get Push Status 

                $check_push_status = ChatMessage::where('provider_id' , $request->provider_id)->where('type' , 'up')->orderBy('updated_at' , 'desc')->first();

                if($check_push_status) {
                    
                    $push_send_status = $check_push_status->delivered ? 0 : 1;
                }

                if($push_send_status) {

                    $title = tr('new_message_from_user');

                    // $this->dispatch(new sendPushNotification($request->provider_id,PROVIDER,PUSH_USER_CHAT,$title,$message ,  "" , $chat_data)); 
                }
      
            }

            if($request->type == 'pu' || $request->type == 'hu') {

                $push_send_status = 1;

                if($request->type == 'pu'){
                    $check_push_status = ChatMessage::where('user_id' , $request->user_id)->where('provider_id', $request->provider_id)->where('type' , 'pu')->orderBy('updated_at' , 'desc')->first();
                } else {
                    $check_push_status = ChatMessage::where('user_id' , $request->user_id)->where('host_id', $request->host_id)->where('type' , 'pu')->orderBy('updated_at' , 'desc')->first();
                }

                if($check_push_status) {
                    $push_send_status = $check_push_status->delivered ? 0 : 1;
                }

                if($push_send_status) {

                    $title = tr('new_message_from_provider');

                    // $this->dispatch( new sendPushNotification($request->user_id, USER,PUSH_PROVIDER_CHAT, $title, $message , "" , $chat_data));
                }
            
            }

            if($request->type == 'uh') {

                $push_send_status = 1;

                // Get Push Status 

                $check_push_status = ChatMessage::where('host_id' , $request->host_id)->where('type' , 'uh')->orderBy('updated_at' , 'desc')->first();

                if($check_push_status) {
                    $push_send_status = $check_push_status->delivered ? 0 : 1;
                }

                if($push_send_status) {

                    $title = tr('new_message_from_user');

                    // $this->dispatch(new sendPushNotification($request->host_id,PROVIDER,PUSH_USER_CHAT,$title,$message ,  "" , $chat_data)); 
                }
            
            }

            $chat_message_details = ChatMessage::create($request->all());

            return $this->sendResponse("", "", $chat_message_details);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        }
    
    }

    /**
     * @method chat_messages_update_status()
     * 
     * @uses - To check the status of the message whether delivered or not. 
     *
     * @created vidhya R
     *
     * @updated vidhya R
     * 
     * @param 
     *
     * @return No Response
     *
     */

    public function chat_messages_update_status(Request $request) {

        // Need to update the user status

        if($request->type == 'pu') {

            ChatMessage::where('user_id' , $request->user_id)->where('provider_id' , $request->provider_id)->where('type' , 'pu')->update(['delivered' => 1]);

        } 

        if($request->type == 'up') {

            ChatMessage::where('user_id' , $request->user_id)->where('provider_id' , $request->provider_id)->where('type' , 'up')->update(['delivered' => 1]);

        }

    }

    /**
     * @method service_locations()
     *
     * @uses used get the service_locations lists
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param 
     *
     * @return response of details
     */

    public function service_locations(Request $request) {

        try {

            $service_locations = ServiceLocation::CommonResponse()->where('service_locations.status' , APPROVED)->orderBy('service_locations.name' , 'asc')->get();

            $response_array = ['success' => true, 'data' => $service_locations];

            return $this->sendResponse("", "", $service_locations);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method list_of_constants()
     *
     * @uses used get the list_of_constants lists
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param 
     *
     * @return response of details
     */

    public function list_of_constants(Request $request) {

        $page_types = [
                'API_PAGE_TYPE_HOME' => API_PAGE_TYPE_HOME,
                'API_PAGE_TYPE_CATEGORY' => API_PAGE_TYPE_CATEGORY,
                'API_PAGE_TYPE_SUB_CATEGORY' => API_PAGE_TYPE_SUB_CATEGORY,
                'API_PAGE_TYPE_SERVICE_LOCATION' => API_PAGE_TYPE_SERVICE_LOCATION,
                'API_PAGE_TYPE_SEE_ALL' => API_PAGE_TYPE_SEE_ALL,    
            ];

        $url_types = [
                'URL_TYPE_CATEGORY' => URL_TYPE_CATEGORY,
                'URL_TYPE_SUB_CATEGORY' => URL_TYPE_SUB_CATEGORY,
                'URL_TYPE_LOCATION' => URL_TYPE_LOCATION,
                'URL_TYPE_TOP_RATED' => URL_TYPE_TOP_RATED,
                'URL_TYPE_WISHLIST' => URL_TYPE_WISHLIST,
                'URL_TYPE_RECENT_UPLOADED' => URL_TYPE_RECENT_UPLOADED,
                'URL_TYPE_SUGGESTIONS' => URL_TYPE_SUGGESTIONS,
            ];

        $input_types = [

            'DROPDOWN' => DROPDOWN,
            'CHECKBOX' => CHECKBOX,
            'RADIO' => RADIO,
            'SPINNER' => SPINNER,
            'SPINNER_CALL_SUB_CATEGORY' => SPINNER_CALL_SUB_CATEGORY,
            'SWITCH' => 'SWITCH',
            'RANGE' => RANGE,
            'AVAILABILITY_CALENDAR' => AVAILABILITY_CALENDAR,
            'ABOUT_HOST_SPACE' => ABOUT_HOST_SPACE,
            'INPUT' => INPUT,
            'INPUT_NUMBER' => INPUT_NUMBER,
            'INPUT_TEXT' => INPUT_TEXT,
            'INPUT_GOOGLE_PLACE_SEARCH' => INPUT_GOOGLE_PLACE_SEARCH,
            'MAP_VIEW' => MAP_VIEW,
            'DATE' => DATE,
            'INCREMENT_DECREMENT' => INCREMENT_DECREMENT,
            'UPLOAD' => UPLOAD,
            'UPLOAD_SINGLE' => UPLOAD_SINGLE,
            'UPLOAD_MULTIPLE' => UPLOAD_MULTIPLE,

            ];

        return view('admin.constants')->with(compact('page_types', 'url_types', 'input_types'));

    }

    /**
     * @method email_verify()
     *
     * @uses To verify the email from user and provider.  
     *
     * @created Bhawya
     *
     * @updated Bhawya
     *
     * @param -
     *
     * @return JSON RESPONSE
     */

    public function email_verify(Request $request) {

        if($request->user_id) {

            $user_details = User::find($request->user_id);

            if(!$user_details) {

                return redirect()->away(Setting::get('frontend_url'))->with('flash_error',tr('user_details_not_found'));
            } 

            if($user_details->is_verified == USER_EMAIL_VERIFIED) {

                return redirect()->away(Setting::get('frontend_url'))->with('flash_success' ,tr('user_verify_success'));
            }

            $response = Helper::check_email_verification($request->verification_code , $user_details->id, $error, USER);
            
            if($response) {

                $user_details->is_verified = USER_EMAIL_VERIFIED;       

                $user_details->save();

            } else {

                return redirect()->away(Setting::get('frontend_url'))->with('flash_error' , $error);
            }

        } else {

            $provider_details = Provider::find($request->provider_id);

            if(!$provider_details) {

                return redirect()->away(Setting::get('frontend_url'))->with('flash_error' , tr('provider_details_not_found'));
            }

            if($provider_details->is_verified) {
                return redirect()->away(Setting::get('frontend_url'))->with('flash_success' ,tr('provider_verify_success'));
            }

            $response = Helper::check_email_verification($request->verification_code , $provider_details->id, $error, PROVIDER);

            if($response) {

                $provider_details->is_verified = PROVIDER_EMAIL_VERIFIED;
                
                $provider_details->save();

            } else {

                return redirect()->away(Setting::get('frontend_url'))->with('flash_error' , $error);
            }

        }

        return redirect()->away(Setting::get('frontend_url'));
    
    }

    /**
     * @method cron_bookings_not_checkin_cancel()
     *
     * @uses   
     *
     * @created Vidhya
     *
     * @updated Vidhya
     *
     * @param -
     *
     * @return JSON RESPONSE
     */
    
    public function cron_bookings_not_checkin_cancel(Request $request) {

        // Get booking created and not checked in and greater than (25mins) checkin time

        $checkin_status = [BOOKING_DONE_BY_USER];

        $bookings = Booking::whereIn('status', $checkin_status)->get();

        foreach ($bookings as $key => $booking_details) {

            $booking_details->status = BOOKING_CANCELLED_BY_USER;

            $booking_details->save();

            // Send mail notification to user & provider 

            // Send push notification to user & provider

            // Send bell notification to user & provider
        }

    }

    /**
     * @method weekly_reports_providers()
     *
     * @uses Weekly reports for provider
     *
     * @created Anjana H
     *
     * @updated Anjana H
     *
     * @param -
     *
     * @return 
     */
    public function weekly_reports_providers() {

        if (Setting::get('is_email_notification') == YES) {

            $current_date = Carbon::now();
         
            $week_start_date = $current_date->startOfWeek()->format('Y-m-d H:i');

            $week_end_date = $current_date->endOfWeek(6)->format('Y-m-d H:i');

            $upcoming_status = [BOOKING_ONPROGRESS,BOOKING_INITIATE, BOOKING_DONE_BY_USER, BOOKING_CHECKIN];

            $cancelled_status = [BOOKING_CANCELLED_BY_PROVIDER, BOOKING_CANCELLED_BY_USER];

            $completed_status = [BOOKING_COMPLETED, BOOKING_CHECKOUT];

            $payment_base_query = BookingPayment::where('status', PAID)->whereBetween('created_at', [$week_start_date, $week_end_date]);

            // Get all approved providers list 
            $providers = Provider::where('status',PROVIDER_APPROVED)->where('is_verified',PROVIDER_EMAIL_VERIFIED)->pluck('id');

            foreach($providers->chunk(10) as $key => $provider_ids) { 

                foreach($provider_ids as $key => $provider_id) { 

                    $provider_details = $provider_data['provider'] = Provider::find($provider_id);

                    if($provider_data['provider']) {

                        $provider_data['total_spaces'] = Host::count();
                        
                        $booking_base_query = Booking::whereBetween('created_at',[$week_start_date, $week_end_date])->where('provider_id',$provider_id);

                        // Total Bookings

                        $provider_data['total_bookings'] = $booking_base_query->count();

                        $provider_data['total_upcoming_bookings'] = $booking_base_query->whereIn('bookings.status', $upcoming_status)->count();

                        $booking_base_query = Booking::whereBetween('created_at',[$week_start_date, $week_end_date])->where('provider_id',$provider_id);

                        $provider_data['total_completed_bookings'] = $booking_base_query->whereIn('bookings.status', $completed_status)->count();
                        
                        $booking_base_query = Booking::whereBetween('created_at',[$week_start_date, $week_end_date])->where('provider_id',$provider_id);

                        $provider_data['total_cancelled_bookings'] = $booking_base_query->whereIn('bookings.status', $cancelled_status)->count();

                        $provider_data['week_start_date'] = common_date(date('d M Y',strtotime($week_start_date)),$provider_details->timezone, 'd M Y');

                        $provider_data['week_end_date'] = common_date(date('d M Y',strtotime($week_end_date)),$provider_details->timezone, 'd M Y');
                         // Payment details

                        $payment_base_query = $payment_base_query->where('provider_id', $provider_id);
                        
                        $provider_data['total_provider_amount'] = $payment_base_query->sum('provider_amount');

                        $provider_data['title'] = Setting::get('site_name', 'RentCubo').'_Weekly_status_report_'.date('Y-m-d');

                        $email_data['page'] = "emails.reports.provider_weekly_report";

                        $email_data['email'] = $provider_details->email;

                        $email_data['file_name'] = $provider_details->name."_".date('Y-m-d',strtotime($current_date));

                        $email_data['file_path'] = FILE_PATH_PROVIDER_REPORT;

                        $data['provider_details'] = $provider_details->toArray();
                        
                        $email_data['provider_details'] = $data;
                        
                        $email_data['report_data'] = $provider_data;

                        $email_data['subject'] = tr('weekly_report').$provider_data['week_start_date'] . ' - '. $provider_data['week_end_date'];

                        $email_data['is_pdf_attached'] = YES;

                        // $this->dispatch(new SendEmailJob($email_data));
                        
                    }
                }

            }
           

        } else {

            Log::info("Provider reports: email notification not activated");   
        }     

    }


    /**
     * @method weekly_reports_admin()
     *
     * @uses Weekly reports for admin 
     *
     * @created Anjana H
     *
     * @updated Anjana H
     *
     * @param -
     *
     * @return 
     */
    public function weekly_reports_admin() {

        if (Setting::get('is_email_notification') == YES) {
            
            $admin_details = Admin::where('status',APPROVED)->first();

            $admin_data['admin'] = $admin_details;

            $current_date = Carbon::now();
           
            $week_start_date = $current_date->startOfWeek()->format('Y-m-d H:i:s');

            $week_end_date = $current_date->endOfWeek()->format('Y-m-d H:i:s');

            $upcoming_status = [BOOKING_ONPROGRESS,BOOKING_INITIATE, BOOKING_DONE_BY_USER, BOOKING_CHECKIN];

            $cancelled_status = [BOOKING_CANCELLED_BY_PROVIDER, BOOKING_CANCELLED_BY_USER];

            $completed_status = [BOOKING_COMPLETED, BOOKING_CHECKOUT];

            $booking_base_query = Booking::whereBetween('created_at',[$week_start_date, $week_end_date]);

            $admin_data['total_spaces'] = Host::whereBetween('created_at',[$week_start_date, $week_end_date])->count();

            $admin_data['total_bookings'] = $booking_base_query->count();

            $admin_data['total_completed_bookings'] = $booking_base_query->whereIn('bookings.status', $completed_status)->count();

            $booking_base_query = Booking::whereBetween('created_at',[$week_start_date, $week_end_date]);

            $admin_data['total_cancelled_bookings'] = $booking_base_query->whereIn('bookings.status', $cancelled_status)->count();

            $booking_base_query = Booking::whereBetween('created_at',[$week_start_date, $week_end_date]);

            $admin_data['total_upcoming_bookings'] = $booking_base_query->whereIn('bookings.status', $upcoming_status)->count();

            $admin_data['week_start_date'] = common_date(date('d M Y',strtotime($week_start_date)), $admin_details->timezone, 'd M Y');
           
            $admin_data['week_end_date'] = common_date(date('d M Y',strtotime($week_end_date)),$admin_details->timezone, 'd M Y');
            
            $admin_data['total_revenue'] = BookingPayment::whereBetween('created_at',[$week_start_date, $week_end_date])->where('status', PAID)->sum('booking_payments.total');

            $email_data['page'] = "emails.reports.admin_weekly_report";

            $email_data['email'] = $admin_details->email;

            $admin_data['title'] = Setting::get('site_name', 'RentCubo').'_Weekly_statu_report_'.date('Y-m-d');

            $email_data['file_name'] = $admin_details->name."_".date('Y-m-d',strtotime($current_date));
            
            $email_data['file_path'] = FILE_PATH_ADMIN_REPORT;

            $email_data['is_pdf_attached'] = YES;

            $email_data['subject'] = tr('weekly_report').$admin_data['week_start_date'] . ' - '. $admin_data['week_end_date'];

            $email_data['report_data'] = $admin_data;

            $data['admin_details'] = $admin_details->toArray();
            
            $email_data['admin_details'] = $data;
            
            // $this->dispatch(new SendEmailJob($email_data));

        } else {

            Log::info("Admin reports: email notification not activated");   
        }    

    }


    /**
     * @method auto_bookings_checkout()
     *
     * @uses Automatic checkout users if they are not checkout on the given date
     *
     * @created Bhawya
     *
     * @updated Bhawya
     *
     * @param -
     *
     * @return JSON RESPONSE
     */
    
    public function auto_bookings_checkout(Request $request) {

        Log::info("cron for automatic checkout");

        // If user not checkout. Automatic chekout after 30minutes
        
        $today = date("Y-m-d H:i:s", strtotime("+30 minutes"));
        
        $bookings = Booking::where('status', BOOKING_CHECKIN)
            ->whereDate('checkout',$today)
            ->get();

        Log::info("Checkout Bookings - ".count($bookings));

        if($bookings) {

            foreach ($bookings as $key => $booking_details) {

                $booking_details->status = BOOKING_CHECKOUT;

                $booking_details->checkout = date("Y-m-d H:i:s");

                $booking_details->save();

                $job_data['booking_details'] = $booking_details;

                dispatch(new BookingsCheckOutJob($job_data));

            }

        } else {
            Log::info('NO bookings found');
        }
        

    }

    /**
     * @method auto_bookings_checkin()
     *
     * @uses Automatic checkin users if they are not checkin
     *
     * @created Bhawya
     *
     * @updated Bhawya
     *
     * @param -
     *
     * @return
     */
    
    public function auto_bookings_checkin(Request $request) {

        Log::info("cron for automatic checkin");

        // If user not checkin. Automatic chekin after 30minutes
        $today = date("Y-m-d H:i:s", strtotime("+30 minutes"));
        
        $bookings = Booking::where('status', BOOKING_DONE_BY_USER)
            ->whereDate('checkin',$today)
            ->get();
        
        Log::info("checkin Bookings - ".$today);

        if($bookings) {

            foreach ($bookings as $key => $booking_details) {

                $booking_details->status = BOOKING_CHECKIN;

                $booking_details->checkin = date("Y-m-d H:i:s");

                $booking_details->save();

                $job_data['booking_details'] = $booking_details;

                dispatch(new BookingsCheckInJob($job_data));

            }

        } else {

            Log::info('NO bookings found');

        }
        

    }

    /**
     * @method get_amenities()
     *
     * @uses Used to get amenities based on the hot type
     *
     * @created Bhawya
     *
     * @updated Bhawya
     *
     * @param -
     *
     * @return
     */
    public function get_amenities(Request $request) {

        $amenities = Lookups::Approved()->areAmenities()->where('type', $request->host_type)->select('id as amenity_lookup_id','key','value')->get();

        $view_page = view('admin.spaces._amenities')->with('amenitie_details' , $amenities)->render();

        $response_array = ['success' =>  true , 'view' => $view_page];

        return response()->json($response_array , 200);

    }



    /**
     * @method demo_credential_cron()
     *
     * @uses To update demo login credentials.
     *
     * @created Anjana H
     *
     * @updated Anjana H
     *
     * @param  
     *
     * @return 
     */
    public function demo_credential_cron() {

        Log::info('Demo Credential CRON STARTED');

        try {
            
            DB::beginTransaction(); 

            $demo_admin = Setting::get('demo_admin_email');

            $admin_details = Admin::where('email' ,$demo_admin)->first();

            if(!$admin_details) {

                $admin_details = new Admin;
                $admin_details->name = 'Admin';
                $admin_details->picture = envfile('APP_URL')."/placeholder.jpg";
                $admin_details->status = 1;
            }

            $admin_details->email = $demo_admin;            
            $admin_details->password = Hash::make(Setting::get('demo_admin_password'));
            
            $demo_user = Setting::get('demo_user_email');

            $user_details = User::where('email' ,$demo_user)->first();
            
            if(!$user_details) {

                $user_details = new User;
                $user_details->unique_id = uniqid();
                $user_details->username = 'userdemo';
                $user_details->name = 'User';
                $user_details->first_name = 'User';
                $user_details->last_name = 'User';
                $user_details->picture ="https://admin-rentpark.rentcubo.info/placeholder.jpg";
                $user_details->login_by ="manual";
                $user_details->device_type = "web";
                $user_details->status = USER_APPROVED;
                $user_details->is_verified = USER_EMAIL_VERIFIED;
                $user_details->user_type = 0; 
                $user_details->payment_mode = COD;
                $user_details->language_id = 1;
                $user_details->registration_steps = 1;
                $user_details->token = Helper::generate_token();
                $user_details->token_expiry = Helper::generate_token_expiry();
            }

            $user_details->email = $demo_user;            
            $user_details->password = Hash::make(Setting::get('demo_user_password')); 

            $demo_provider = Setting::get('demo_provider_email');

            $provider_details = Provider::where('email' ,$demo_provider)->first();           

            if(!$provider_details) {

                $provider_details = new Provider;
                $provider_details->unique_id = uniqid();
                $provider_details->username = 'providerdemo';
                $provider_details->name = 'Provider';
                $provider_details->first_name = 'Provider';
                $provider_details->last_name = 'Provider';
                $provider_details->picture ="https://admin-rentpark.rentcubo.info/placeholder.jpg";
                $provider_details->login_by ="manual";
                $provider_details->device_type = "web";
                $provider_details->status = PROVIDER_APPROVED;
                $provider_details->is_verified = PROVIDER_EMAIL_VERIFIED;
                $provider_details->provider_type = 0;
                $provider_details->payment_mode = COD;
                $provider_details->language_id = 1;
                $provider_details->registration_steps = 1;
                $provider_details->token = Helper::generate_token();
                $provider_details->token_expiry = Helper::generate_token_expiry();
            }

            $provider_details->email = $demo_provider;            
            $provider_details->password = Hash::make(Setting::get('demo_provider_password'));   

            if( $provider_details->save() && $user_details->save() && $admin_details->save()) {

                DB::commit();

            } else {

                throw new Exception("Demo Credential CRON- Credential Could not be updated", 101);                
            }
            
         } catch(Exception $e) {

            DB::rollback();

            $error = $e->getMessage();

            Log::info('Demo Credential CRON Error:'.print_r($error , true));

        }       
        
        Log::info('Demo Credential CRON END');

    }


}
