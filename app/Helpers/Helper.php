<?php 

namespace App\Helpers;

use Mailgun\Mailgun;

use Hash, Exception, Auth, Mail, File, Log, Storage, Setting, DB, Validator;

use App\Admin, App\User, App\Provider;

use App\Settings, App\StaticPage;

use App\Jobs\SendEmailJob;

class Helper {

    public static function clean($string) {

        $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.

        return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
    }

    public static function generate_token() {
        
        return Helper::clean(Hash::make(rand() . time() . rand()));
    }

    public static function generate_token_expiry() {

        $token_expiry_hour = Setting::get('token_expiry_hour') ?: 1;
        
        return time() + $token_expiry_hour*3600;  // 1 Hour
    }

    // Note: $error is passed by reference
    
    public static function is_token_valid($entity, $id, $token, &$error) {

        if (
            ( $entity== USER && ($row = User::where('id', '=', $id)->where('token', '=', $token)->first()) ) ||
            ( $entity== PROVIDER && ($row = Provider::where('id', '=', $id)->where('token', '=', $token)->first()) )
        ) {

            if ($row->token_expiry > time()) {
                // Token is valid
                $error = NULL;
                return true;
            } else {
                $error = ['success' => false, 'error' => api_error(1003), 'error_code' => 1003];
                return FALSE;
            }
        }

        $error = ['success' => false, 'error' => api_error(1004), 'error_code' => 1004];
        return FALSE;
   
    }

    public static function generate_email_code($value = "") {

        return uniqid($value);
    }

    public static function generate_email_expiry() {

        $token_expiry = Setting::get('token_expiry_hour') ?: 1;
            
        return time() + $token_expiry*3600;  // 1 Hour

    }

    // Check whether email verification code and expiry

    public static function check_email_verification($verification_code , $user_id , &$error,$common) {

        if(!$user_id) {

            $error = tr('user_id_empty');

            return FALSE;

        } else {

            if($common == USER) {
                $user_details = User::find($user_id);
            } else if($common == PROVIDER) {
                $user_details = Provider::find($user_id);
            }
        }

        // Check the data exists

        if($user_details) {

            // Check whether verification code is empty or not

            if($verification_code) {

                // Log::info("Verification Code".$verification_code);

                // Log::info("Verification Code".$user_details->verification_code);

                if ($verification_code ===  $user_details->verification_code ) {

                    // Token is valid

                    $error = NULL;

                    // Log::info("Verification CODE MATCHED");

                    return true;

                } else {

                    $error = tr('verification_code_mismatched');

                    // Log::info(print_r($error,true));

                    return FALSE;
                }

            }
                
            // Check whether verification code expiry 

            if ($user_details->verification_code_expiry > time()) {

                // Token is valid

                $error = NULL;

                Log::info(tr('token_expiry'));

                return true;

            } else if($user_details->verification_code_expiry < time() || (!$user_details->verification_code || !$user_details->verification_code_expiry) ) {

                $user_details->verification_code = Helper::generate_email_code();
                
                $user_details->verification_code_expiry = Helper::generate_email_expiry();
                
                $user_details->save();

                // If code expired means send mail to that user

                $email_data['page'] = "emails.welcome";

                $email_data['email'] = $user_details->email;
                
                $email_data['user_details'] = $user_details;
               
                // $email_data['pdf'] = $pdf;

                dispatch(new SendEmailJob($email_data));

                $error = tr('verification_code_expired');

                Log::info(print_r($error,true));

                return FALSE;
            }
       
        }

    }
    
    public static function generate_password() {

        $new_password = time();
        $new_password .= rand();
        $new_password = sha1($new_password);
        $new_password = substr($new_password,0,8);
        return $new_password;
    }

    public static function file_name() {

        $file_name = time();
        $file_name .= rand();
        $file_name = sha1($file_name);

        return $file_name;    
    }

    public static function upload_file($picture , $folder_path = COMMON_FILE_PATH) {

        $file_path_url = "";

        $file_name = Helper::file_name();

        $ext = $picture->getClientOriginalExtension();

        $local_url = $file_name . "." . $ext;

        $inputFile = base_path('public'.$folder_path.$local_url);

        $picture->move(public_path().$folder_path, $local_url);

        $file_path_url = Helper::web_url().$folder_path.$local_url;

        return $file_path_url;
    
    }

    public static function web_url() 
    {
        return url('/');
    }
    
    public static function delete_file($picture, $path = COMMON_FILE_PATH) {

        if ( file_exists( public_path() . $path . basename($picture))) {

            File::delete( public_path() . $path . basename($picture));
      
        } else {

            return false;
        }  

        return true;    
    }
 
    public static function send_email($page,$subject,$email,$email_data,$model) {

        // check the email notification

        if(Setting::get('is_email_notification') == YES) {

            // Don't check with envfile function. Because without configuration cache the email will not send

            if( config('mail.username') &&  config('mail.password')) {

                try {

                    $site_url=url('/');

                    $isValid = 1;

                    if(envfile('MAIL_DRIVER') == 'mailgun' && Setting::get('MAILGUN_PUBLIC_KEY')) {

                        Log::info("isValid - STRAT");

                        # Instantiate the client.

                        $email_address = new Mailgun(Setting::get('MAILGUN_PUBLIC_KEY'));

                        $validateAddress = $email;

                        # Issue the call to the client.
                        $result = $email_address->get("address/validate", ['address' => $validateAddress]);

                        # is_valid is 0 or 1

                        $isValid = $result->http_response_body->is_valid;

                        Log::info("isValid FINAL STATUS - ".$isValid);

                    }

                    if($isValid) {

                        /*Mail::queue($page, ['email_data' => $email_data,'site_url' => $site_url], 
                                function ($message) use ($email, $subject) {

                                    $message->to($email)->subject($subject);
                                }
                        )*/
                        $responce = Mail::to($email)->queue($model);
                        Log::info("SUCCESS - ");
                        if (Mail::failures()) {
                            
                            throw new Exception(api_error(116) , 116);
                            

                        } else {

                            $message = api_success(102);

                            $response_array = ['success' => true , 'message' => $message];

                            return json_decode(json_encode($response_array));
                            
                        }

                    } else {

                        $error = api_error(115);

                        throw new Exception($error, 115);                  

                    }

                } catch(\Exception $e) {

                    $error = $e->getMessage();

                    $error_code = $e->getCode();

                    $response_array = ['success' => false , 'error' => $error , 'error_code' => $error_code];
                    
                    return json_decode(json_encode($response_array));

                }
            
            } else {

                $error = api_error(106);

                $response_array = ['success' => false , 'error' => $error , 'error_code' => 106];
                    
                return json_decode(json_encode($response_array));

            }
        
        } else {
            
            Log::info("email notification disabled by admin");

            $error = api_error(227);

            $response_array = ['success' => false , 'error' => $error , 'error_code' => 227];
                
            return json_decode(json_encode($response_array));
        }
    
    }   

    public static function error_message($code , $other_key = "") {

        switch($code) {
            
            case 101:
                $string = tr('invalid_input');
                break;
            case 102:
                $string = tr('username_password_not_match');
                break;
            case 103:
                $string = tr('user_details_not_save');
                break;
            case 104: 
                $string = tr('invalid_email_address');
                break;
            case 105: 
                $string = tr('mail_send_failure');
                break;
            case 106: 
                $string = tr('mail_not_configured');
                break;
            case 107:
                $string = tr('stripe_not_configured');
                break;
            case 108:
                $string = tr('password_not_correct');
                break;
            case 109:
                $string = tr('user_no_payment_mode');
                break;
            case 110:
                $string = tr('user_payment_not_saved');
                break;
            case 111:
                $string = tr('no_default_card');
                break;
            case 112:
                $string = tr('no_default_card');
                break;
            case 113:
                $string = tr('stripe_payment_not_configured');
                break;
            case 114:
                $string = tr('stripe_payment_failed');
                break;
            case 115:
                $string = tr('stripe_payment_card_add_failed');
                break;
            case 116:
                $string = tr('user_forgot_password_deny_for_social_login');
                break;
            case 117:
                $string = tr('forgot_password_email_verification_error');
                break;
            case 118:
                $string = tr('forgot_password_decline_error');
                break;
            case 119:
                $string = tr('user_change_password_deny_for_social_login');
                break;
            
            case 200:
                $string = tr('host_details_not_found');
                break;
            case 201:
                $string = tr('provider_details_not_found');
                break;
            case 202:
                $string = tr('invalid_request_input');
                break;
            case 203:
                $string = tr('provider_subscription_not_found');
                break;
            case 203:
                $string = tr('provider_subscription_payment_error');
                break;
            case 204:
                $string = tr('host_delete_error');
                break;
            case 205:
                $string = tr('account_delete_failed');
                break;
            case 206:
                $string = tr('booking_not_found');
                break;
            case 207:
                $string = tr('provider_booking_cancel_failed');
                break;
            case 208:
                $string = tr('user_booking_cancel_failed');
                break;
            case 209:
                $string = tr('booking_already_canceled');
                break;
            case 210:
                $string = tr('host_not_available_on_selected_dates');
                break;
            case 211:
                $string = tr('booking_total_guests_should_greater_equal_host_min_guests', $other_key);
                break;
            case 212:
                $string = tr('booking_total_guests_should_less_equal_host_max_guests', $other_key);
                break;
            case 213:
                $string = tr('checkin_checkout_date_error', $other_key);
                break;
            case 214:
                $string = tr('booking_not_eligible_for_review');
                break;
            case 215:
                $string = tr('user_details_not_found');
                break;
            case 216:
                $string = tr('wishlist_delete_error');
                break;
            case 217:
                $string = tr('booking_cancel_not_allowed_after_checkin');
                break;
            case 218:
                $string = tr('booking_review_already_done');
                break;
            case 219:
                $string = tr('booking_review_failed');
                break;
            case 220:
                $string = tr('booking_checkin_status_mismatch');
                break;
            case 221:
                $string = tr('booking_checkin_failed');
                break;
            case 222:
                $string = tr('booking_checkedin_already');
                break;
            case 223:
                $string = tr('booking_checkout_status_mismatch');
                break;
            case 224:
                $string = tr('booking_checkout_failed');
                break;
            case 225:
                $string = tr('booking_checkedout_already');
                break;
            case 226:
                $string = tr('booking_already_craeted');
                break;
            case 227:
                $string = tr('email_notification_disabled_by_admin');
                break;
            case 228:
                $string = tr('account_details_not_save');
                break;
            case 229:
                $string = tr('provider_redeems_not_found');
                break;
            case 230:
                $string = tr('amount_is_greater_than_remaining_amount');
                break;
            case 231:
                $string = tr('user_refunds_not_found');
                break;
            case 232:
                $string = tr('document_not_found');
                break;
            case 233:
                $string = tr('provider_document_not_found');
                break;
            case 234:
                $string = tr('checkin_time_must_be_on_or_before_10_minutes');
                break;
            case 235:
                $string = tr('checkin_verification_code_mismatch');
                break;
            case 236:
                $string = tr('checkin_time_must_be_on_currrent_date');
                break;
                


            // DONT THE INCREASE NUMBERS FROM ABOVE.

            case 500:
                $string = tr('host_availability_list_delete_error');
                break;
            case 501:
                $string = tr('host_availability_list_not_found');
                break;
            case 502:
                $string = tr('bookings_create_host_is_not_available');
                break;
            case 503:
                $string = tr('bookings_create_vehicle_already_booked');
                break;
            
            // USE BELOW CONSTANTS FOR AUTHENTICATION CHECK
            case 1000:
                $string = tr('user_login_decline');
                break;
            case 1001:
                $string = tr('user_not_verified');
                break;
            case 1002:
                $string = tr('user_details_not_found');
                break;
            case 1003:
                $string = tr('token_expiry');
                break;
            case 1004:
                $string = tr('invalid_token');
                break;
            case 1005:
                $string = tr('without_id_token_user_accessing_request');
                break;
            case 1006:
                $string = tr('provider_details_not_found');
                break;
            case 1007:
                $string = tr('provider_waiting_for_admin_approval'); // Note: only for message
                break;
            case 1008:
                $string = tr('user_waiting_for_admin_approval'); // Note: only for message
                break;
            case 1009:
                $string = tr('provider_subscribe_and_create_host'); // Note: only for message
                break;
            case 1010:
                $string = tr('min_and_total_guest_less_than_total_guest');
                break;
            case 1011:
                $string = tr('provider_login_decline');
                break;

            default:
                $string = tr('unknown_error_occured');
        }

        return $string;
    
    }

    public static function success_message($code) {

        switch($code) {
            case 101:
                $string = tr('login_success');
                break;
            case 102:
                $string = tr('mail_sent_success');
                break;
            case 103:
                $string = tr('account_delete_success');
                break;
            case 104:
                $string = tr('password_change_success');
                break;
            case 105:
                $string = tr('card_added_success');
                break;
            case 106:
                $string = tr('logout_success');
                break;
            case 107:
                $string = tr('card_deleted_success');
                break;
            case 108:
                $string = tr('card_default_success');
                break;  
            case 109:
                $string = tr('user_payment_mode_update_success');
                break;
            
            case 200:
                $string = tr('wishlist_add_success');
                break;
            case 201:
                $string = tr('wishlist_delete_success');
                break;
            case 202:
                $string = tr('wishlist_clear_success');
                break;
            case 203:
                $string = tr('booking_done_success');
                break;
            case 204:
                $string = tr('bell_notification_updated');
                break;
            case 205: 
                $string = tr('provider_subscription_payment_success');
                break;
            case 206: 
                $string = tr('notification_enable');
                break;
            case 207: 
                $string = tr('notification_disable');
                break;
            case 208: 
                $string = tr('host_publish_success');
                break;
            case 209: 
                $string = tr('host_unpublish_success');
                break;
            case 210:
                $string = tr('host_delete_success');
                break;
            case 211:
                $string = tr('host_availability_updated');
                break;
            case 212:
                $string = tr('provider_cancel_success');
                break;
            case 213:
                $string = tr('user_cancel_success');
                break;
            case 214:
                $string = tr('user_profile_update_success');
                break;
            case 215:
                $string = tr('provider_profile_update_success');
                break;
            case 216:
                $string = tr('provider_booking_review_success');
                break;
            case 217:
                $string = tr('user_booking_review_success');
                break;
            case 218:
                $string = tr('user_booking_checkin_success');
                break;
            case 219:
                $string = tr('user_booking_checkout_success');
                break;
            case 220:
                $string = tr('user_booking_review_success');
                break;
            case 221:
                $string = tr('api_host_updated_success');
                break;
            case 222:
                $string = tr('account_details_saved_success');
                break;
            case 223:
                $string = tr('amount_paid_success');
                break;
            case 224:
                $string = tr('user_refund_success');
                break;
            case 225:
                $string = tr('document_upload_success');
                break;
            case 226:
                $string = tr('document_delete_success');
                break;

            case 500:
                $string = tr('host_availability_list_deleted');
                break;
            case 501:
                $string = tr('booking_done_success_waiting_for_provider_approval');
                break;
            case 502:
                $string = tr('bookings_approved_success');
                break;
            case 503:
                $string = tr('bookings_rejected_success');
                break;
            case 504:
                $string = tr('notification_send_success');
                break;


            default:
                $string = "";
        
        }
        
        return $string;
    
    }

    public static function push_message($code) {

        switch ($code) {
            case 601:
                $string = tr('bookings_cancelled_by_provider');
                break;
            case 602:
                $string = tr('reviews_updated_by_provider');
                break;

            case 603:
                $string = tr('bookings_created_for_the_host');
                break;

            case 604:
                $string = tr('cancelled_by_the_user');
                break;

            case 605:
                $string = tr('reviews_updated_by_user');
                break;

            case 606:
                $string = tr('checked_in_by_the_user');
                break;

            case 607:
                $string = tr('checked_out_by_the_user');
                break;

            case 608:
                $string = tr('host_approved_by_admin');
                break;

            case 609:
                $string = tr('host_declined_by_admin');
                break;

            case 610:
                $string = tr('extra_hours_payment');
                break;

            case 611:
                $string = tr('provider_checkin');
                break;

            case 612:
                $string = tr('provider_checkout');
                break;
            
            case 613:
                $string = tr('checkout_notification');
                break;

            case 614:
                $string = tr('bookings_approved_by_provider');
                break;

            case 615:
                $string = tr('bookings_rejected_by_provider');
                break;

            case 616:
                $string = tr('bookings_created_for_the_space');
                break;

            case 617:
                $string = tr('provider_documents_verify');
                break;

            case 618:
                $string = tr('provider_approved');
                break;

            case 619:
                $string = tr('provider_declined');
                break;

            case 620:
                $string = tr('provider_email_verified');
                break;

            case 621:
                $string = tr('provider_email_declined');
                break;

            case 621:
                $string = tr('user_refund_success');
                break;
            case 622:
                $string = tr('bookings_cancelled_by_admin');
                break;
            default:
                $string = "";
        }

        return $string;

    }  

    // Convert all NULL values to empty strings
    public static function null_safe($input_array) {
 
        $new_array = [];

        foreach ($input_array as $key => $value) {

            $new_array[$key] = ($value == NULL) ? "" : $value;
        }

        return $new_array;
    }

    /**
     * Creating date collection between two dates
     *
     * <code>
     * <?php
     * # Example 1
     * generate_date_range("2014-01-01", "2014-01-20", "+1 day", "m/d/Y");
     *
     * # Example 2. you can use even time
     * generate_date_range("01:00:00", "23:00:00", "+1 hour", "H:i:s");
     * </code>
     *
     * @param string since any date, time or datetime format
     * @param string until any date, time or datetime format
     * @param string step
     * @param string date of output format
     * @return array
     */
    public static function generate_date_range($month = "", $year = "", $step = '+1 day', $output_format = 'd/m/Y', $loops = 2) {

        $month = $set_current_month = $month ?: date('F');

        $year = $set_current_year = $year ?: date('Y');

        $last_month = date('F', strtotime('+'.$loops.' months'));

        $dates = $response = [];

        // $response = new \stdClass;

        $response = [];

        $current_loop = 1;

        while ($current_loop <= $loops) {
        
            $month_response = new \stdClass;

            $timestamp = strtotime($set_current_month.' '.$set_current_year); // Get te timestamp from the given 

            $first_date_of_the_month = date('Y-m-01', $timestamp);

            $last_date_of_month  = date('Y-m-t', $timestamp); 

            $dates = [];

            $set_current_date = strtotime($first_date_of_the_month); // time convertions and set dates

            $last_date_of_month = strtotime($last_date_of_month);  // time convertions and set dates

            // Generate dates based first and last dates

            while( $set_current_date <= $last_date_of_month ) {

                $dates[] = date($output_format, $set_current_date);

                $set_current_date = strtotime($step, $set_current_date);
            }

            $month_response->month = $set_current_month;

            $month_response->total_days = count($dates);

            $month_response->dates = $dates;


            $set_current_month = date('F', strtotime("+".$current_loop." months", $last_date_of_month));

            $set_current_year = date('Y', strtotime("+".$current_loop." months", $last_date_of_month));


            $current_loop++;

            array_push($response, $month_response);

        }

        return $response;
    }

    /**
     *
     * @method get_months()
     *
     * @uses get months list or get month number
     *
     * @created vithya R
     *
     * @updated vithya R
     *
     * @param
     *
     * @return 
     */

    public static function get_months($get_month_name = "") {

        $months = ['01' => 'January', '02' => 'February','03' => 'March','04' => 'April','05' => 'May','06' => 'June','07' => 'July ','08' => 'August','09' => 'September','10' => 'October','11' => 'November','12' => 'December'];

        if($get_month_name) {

            return $months[$get_month_name];

        }

        return $months;
    }

    /**
     *
     * @method get_host_types()
     *
     * @uses get host types
     *
     * @created vithya R
     *
     * @updated vithya R
     *
     * @param
     *
     * @return 
     */

    public static function get_host_types($host_type = "") {

        $host_data[0]['title'] = tr('user_host_type_entire_place_title');
        $host_data[0]['search_key'] = HOST_ENTIRE;
        $host_data[0]['description'] = tr('user_host_type_entire_place_description');

        $host_data[1]['title'] = tr('user_host_type_private_title');
        $host_data[1]['search_key'] = HOST_PRIVATE;
        $host_data[1]['description'] = tr('user_host_type_private_description');
        
        $host_data[2]['title'] = tr('user_host_type_shared_title');
        $host_data[2]['search_key'] = HOST_SHARED;
        $host_data[2]['description'] = tr('user_host_type_shared_description');

        return $host_data;
    
    }

    /**
     *
     * @method get_host_types()
     *
     * @uses get host types
     *
     * @created vithya R
     *
     * @updated vithya R
     *
     * @param
     *
     * @return 
     */

    public static function get_provider_host_types($host = []) {

        $host_data[0]['key'] = HOST_ENTIRE;
        $host_data[0]['value'] = tr('user_host_type_entire_place_title');
        $host_data[0]['description'] = tr('user_host_type_entire_place_description');
        $host_data[0]['picture'] = "";


        $host_data[1]['key'] = HOST_PRIVATE;
        $host_data[1]['value'] = tr('user_host_type_private_title');
        $host_data[1]['description'] = tr('user_host_type_private_description');
        $host_data[1]['picture'] = "";

        $host_data[2]['key'] = HOST_SHARED;
        $host_data[2]['value'] = tr('user_host_type_shared_title');
        $host_data[2]['description'] = tr('user_host_type_shared_description');
        $host_data[2]['picture'] = "";

        $host_data = json_decode(json_encode($host_data));

        foreach ($host_data as $key => $value) {

            $value->is_checked = NO;

            if($host) {

                $value->is_checked = $host->host_type == $value->key ? YES : NO;

            }
        }

        return $host_data;
    
    }
    /**
     *
     * @method get_bathrooms()
     *
     * @uses get host types
     *
     * @created vithya R
     *
     * @updated vithya R
     *
     * @param
     *
     * @return 
     */

    public static function get_bathrooms($host_type = "") {

       //$data = [0 => "0 Bathrooms", ]
    
    }

    public static function get_times() {

        $times = ['flexible' => 'Flexible', '12 AM' => '12 AM(midnight)', '1 AM' => '1 AM', '2 AM' => '2 AM', '3 AM' => '3 AM', '4 AM' => '4 AM', '5 AM' => '5 AM', '6 AM' => '6 AM', '7 AM' => '7 AM', '8 AM' => '8 AM', '9 AM' => '9 AM', '10 AM' => '10 AM', '11 AM' => '11 AM', '12 PM' => '12 PM(Afternoon)', '1 PM' => '1 PM', '2 PM' => '2 PM', '3 PM' => '3 PM', '4 PM' => '4 PM', '5 PM' => '5 PM', '6 PM' => '6 PM', '7 PM' => '7 PM', '8 PM' => '8 PM', '9 PM' => '9 PM', '10 PM' => '10 PM', '11 PM' => '11 PM'];

        return $times;
    }

    /**
     *
     * @uses used to check provider subscription status
     * 
     */
    
    public static function check_provider_type($provider_details) {

        $provider_type = $provider_details->provider_type;

        if($provider_details->provider_type == PROVIDER_TYPE_PAID) {

        }

        return $provider_type;

    }

    /**
     * @method settings_generate_json()
     *
     * @uses used to update settings.json file with updated details.
     *
     * @created vidhya
     * 
     * @updated vidhya
     *
     * @param -
     *
     * @return boolean
     */
    
    public static function settings_generate_json() {

        $basic_keys = ['site_name', 'site_logo', 'site_icon', 'currency', 'currency_code', 'google_analytics', 'header_scripts', 'body_scripts', 'facebook_link', 'linkedin_link', 'twitter_link', 'google_plus_link', 'pinterest_link', 'demo_user_email', 'demo_user_password', 'demo_provider_email', 'demo_provider_password', 'chat_socket_url', 'google_api_key', 'playstore_user', 'playstore_provider', 'appstore_user', 'appstore_provider'];

        // $settings = Settings::whereIn('key', $basic_keys)->get();
        $settings = Settings::get();

        $sample_data = [];

        foreach ($settings as $key => $setting_details) {

            $sample_data[$setting_details->key] = $setting_details->value;
        }

        $footer_first_section = StaticPage::select('id as page_id', 'unique_id', 'type as page_type', 'title')->where('section_type', STATIC_PAGE_SECTION_1)->get();

        $footer_second_section = StaticPage::select('id as page_id', 'unique_id', 'type as page_type', 'title')->where('section_type', STATIC_PAGE_SECTION_2)->get();

        $footer_third_section = StaticPage::select('id as page_id', 'unique_id', 'type as page_type', 'title')->where('section_type', STATIC_PAGE_SECTION_3)->get();

        $footer_fourth_section = StaticPage::select('id as page_id', 'unique_id', 'type as page_type', 'title')->where('section_type', STATIC_PAGE_SECTION_4)->get();

        $sample_data['footer_first_section'] = $footer_first_section;

        $sample_data['footer_second_section'] = $footer_second_section;

        $sample_data['footer_third_section'] = $footer_third_section;

        $sample_data['footer_fourth_section'] = $footer_fourth_section;

        // Social logins

        $social_login_keys = ['FB_CLIENT_ID', 'FB_CLIENT_SECRET', 'FB_CALL_BACK' , 'TWITTER_CLIENT_ID', 'TWITTER_CLIENT_SECRET', 'TWITTER_CALL_BACK', 'GOOGLE_CLIENT_ID', 'GOOGLE_CLIENT_SECRET', 'GOOGLE_CALL_BACK'];

        $social_logins = Settings::whereIn('key', $social_login_keys)->get();

        $social_login_data = [];

        foreach ($social_logins as $key => $social_login_details) {

            $social_login_data[$social_login_details->key] = $social_login_details->value;
        }

        $sample_data['social_logins'] = $social_login_data;

        $data['data'] = $sample_data;

        $data = json_encode($data);

        $file_name = public_path('/default-json/settings.json');

        File::put($file_name, $data);
    }

    /**
     *
     * @method get_week_days()
     *
     * @uses get week days
     *
     * @created Anjana H
     *
     * @updated Anjana H
     *
     * @param
     *
     * @return 
     */

    public static function get_week_days($day = "", $available_days = "") {

        $days = [                 
                    ['key'=> '1', 'value' => 'Sunday'],
                    ['key'=> '2', 'value' => 'Monday'],
                    ['key'=> '3', 'value' => 'Tuesday'],
                    ['key'=> '4', 'value' => 'Wednesday'],
                    ['key'=> '5', 'value' => 'Thursday'],
                    ['key'=> '6', 'value' => 'Friday'],
                    ['key'=> '7', 'value' => 'Saturday']
                ];

        $host_available_days = explode(",",$available_days);

        for( $i=0 ;$i< sizeof($days); $i++) {
           
            $days[$i]['is_selected'] = in_array($days[$i]['key'], $host_available_days);
        }
        
        return $days;    
    }

     /**
     * @method custom_validator()
     *
     * @uses validation
     *
     * @created Akshata
     *
     * @updated 
     *
     * @param
     *
     * @return 
     */
    public static function custom_validator($request, $request_inputs, $custom_errors = []) {

        $validator = Validator::make($request, $request_inputs, $custom_errors);

        if($validator->fails()) {

            $error = implode_values($validator->messages()->all());

            throw new Exception($error, 101);
               
        }
    }


}


