<?php

namespace App\Repositories;

use App\Helpers\Helper;

use App\Helpers\HostHelper;

use DB, Log, Validator, Exception, Setting;

use App\User;

use App\Host, App\HostGallery, App\HostDetails;

use App\ServiceLocation;

use App\CommonQuestion, App\CommonQuestionAnswer;

use App\HostQuestionAnswer, App\HostAvailability;

class HostRepository {

    /**
     *
     * @method host_list_response()
     *
     * @uses used to get the common list details for hosts
     *
     * @created Vidhya R
     *
     * @updated Vidhya R
     *
     * @param array $host_ids
     *
     * @param integer $user_id
     *
     * @return
     */

    public static function host_list_response($host_ids, $user_id) {

        $hosts = Host::whereIn('hosts.id' , $host_ids)
                            ->orderBy('hosts.updated_at' , 'desc')
                            ->UserBaseResponse()
                            ->get();

        foreach ($hosts as $key => $host_details) {

            $host_details->wishlist_status = NO;

            if($user_id) {

                $host_details->wishlist_status = HostHelper::wishlist_status($host_details->host_id, $user_id);

            }

            $host_details->space_location = $host_details->serviceLocationDetails->name ?? $host_details->host_location;

            $host_details->base_price_formatted = formatted_amount($host_details->base_price);
            
            $host_details->per_day_formatted = formatted_amount($host_details->per_day);

            $host_details->per_day_symbol = tr('list_per_day_symbol');

            $host_details->per_hour_formatted = formatted_amount($host_details->per_hour);

            $host_details->per_hour_symbol = tr('list_per_hour_symbol');

            $host_galleries = HostGallery::where('host_id', $host_details->host_id)->select('picture', 'caption')->skip(0)->take(3)->get();

            $host_details->gallery = $host_galleries;
        }

        return $hosts;

    } 

    /**
     *
     * @method park_hosts_list_response()
     *
     * @uses used to get the common list details for hosts
     *
     * @created Vidhya R
     *
     * @updated Vidhya R
     *
     * @param array $host_ids
     *
     * @param integer $user_id
     *
     * @return
     */

    public static function park_hosts_list_response($host_ids, $user_id, $request = []) {

        $hosts = Host::whereIn('hosts.id' , $host_ids)
                            ->orderBy('hosts.updated_at' , 'desc')
                            ->UserParkResponse()
                            ->get();

        foreach ($hosts as $key => $host_details) {

            $host_details->total_distance = calculate_distance($host_details->latitude, $host_details->longitude, $request->latitude, $request->longitude);

            $host_details->wishlist_status = NO;

            if($user_id) {

                $host_details->wishlist_status = HostHelper::wishlist_status($host_details->host_id, $user_id);

            }
            
            $host_details->per_hour_formatted = formatted_amount($host_details->per_hour);

            $host_details->per_hour_symbol = tr('list_per_hour_symbol');

            $host_galleries = HostGallery::where('host_id', $host_details->host_id)->select('picture', 'caption')->skip(0)->take(3)->get();

            $host_details->gallery = $host_galleries;

            $tax_percentage = Setting::get('tax_percentage', 1)/100;

            $tax_price = $host_details->per_hour * $tax_percentage;

            $host_details->tax_price_formatted = formatted_amount($tax_price);

            $total = $host_details->per_hour + $tax_price;

            $host_details->total_formatted = formatted_amount($total);

            unset($host_details->serviceLocationDetails);
        }

        // Sorting option for website

        if($request->sort_by) {

            if($request->sort_by == SPACE_BEST_MATCH) {

                Log::info("SPACE_BEST_MATCH");

                $hosts = $hosts->sortByDesc('overall_ratings')->values();

            } elseif($request->sort_by == SPACE_CHEAPEST) {
                
                Log::info("SPACE_CHEAPEST");

                $hosts = $hosts->sortBy('per_hour')->values();

            } elseif($request->sort_by == SPACE_CLOSEST) {

                Log::info("SPACE_CLOSEST");

                $hosts = $hosts->sortBy('total_distance')->values();

            } else {
                Log::info("NOTHING,......");

                $hosts = $hosts->sortByDesc('overall_ratings')->values();
            }

        } else {
            Log::info("Hello World");
        }

        return $hosts;

    } 

    /**
     *
     * @method provider_hosts_response()
     *
     * @uses used to get the common list details for hosts
     *
     * @created Vidhya R
     *
     * @updated Vidhya R
     *
     * @param 
     *
     * @return
     */

    public static function provider_hosts_response($host_ids) {

        $hosts = Host::whereIn('hosts.id' , $host_ids)
                        ->select('hosts.id as host_id', 'hosts.space_name', 'hosts.picture as space_picture', 'hosts.host_type as space_type', 'hosts.city as space_location', 'hosts.created_at', 'hosts.updated_at')
                        ->orderBy('hosts.updated_at' , 'desc')
                        ->get();
        return $hosts;

    } 

    /**
     *
     * @method host_gallery_upload()
     *
     * @uses used to get the common list details for hosts
     *
     * @created Vidhya R
     *
     * @updated Vidhya R
     *
     * @param 
     *
     * @return
     */

    public static function host_gallery_upload($files, $host_id, $status = YES, $set_default_picture = NO) {

        $allowedfileExtension=['jpeg','jpg','png'];

        $host = Host::find($host_id);

        $is_host_image = $host ? ($host->picture ? YES : NO): NO;

        $data = [];

        // Single file upload

        if(!is_array($files)) {
            
            $file = $files;

            $host_gallery_details = new HostGallery;

            $host_gallery_details->host_id = $host_id;

            $host_gallery_details->picture = Helper::upload_file($file, FILE_PATH_HOST);

            $host_gallery_details->status = $status;

            $host_gallery_details->save();

            if($is_host_image == NO && $host) {

                $host->picture = $host_gallery_details->picture;

                $host->save();
            }

            if($set_default_picture == YES) {

                $host->picture = $host_gallery_details->picture;

                $host->save();

            }

            $gallery_data = [];

            $gallery_data['host_gallery_id'] = $host_gallery_details->id;

            $gallery_data['space_gallery_id'] = $host_gallery_details->id;

            $gallery_data['file'] = $gallery_data['picture'] = $host_gallery_details->picture;

            array_push($data, $gallery_data);

            return $data;
       
        }

        // Multiple files upload

        foreach($files as $file) {

            $filename = $file->getClientOriginalName();

            $extension = $file->getClientOriginalExtension();

            $check_picture = in_array($extension, $allowedfileExtension);
            
            if($check_picture) {

                $host_gallery_details = new HostGallery;

                $host_gallery_details->host_id = $host_id;

                $host_gallery_details->picture = Helper::upload_file($file, FILE_PATH_HOST);

                $host_gallery_details->status = $status;

                $host_gallery_details->save();

                if($is_host_image == NO && $host) {

                    $host->picture = $host_gallery_details->picture;

                    $host->save();
               
                }

                $gallery_data = [];

                $gallery_data['host_gallery_id'] = $host_gallery_details->id;

                $gallery_data['space_gallery_id'] = $host_gallery_details->id;

                $gallery_data['file'] = $host_gallery_details->picture;

                array_push($data, $gallery_data);

           }
        
        }

        return $data;
    
    }
    
    public static function see_all_section($request) {

        $hosts = []; $title = $description = "";

        switch ($request->url_type) {

            case URL_TYPE_RECENT_UPLOADED:
                $hosts = HostHelper::recently_uploaded_hosts($request);
                $title = tr('URL_TYPE_RECENT_UPLOADED');
                $description = "";
                break;

            case URL_TYPE_TOP_RATED:
                $hosts = HostHelper::top_rated_hosts($request);
                $title = tr('URL_TYPE_TOP_RATED');
                $description = "";
                break;

            case URL_TYPE_SUGGESTIONS:
                $hosts = HostHelper::suggestions($request);
                $title = tr('URL_TYPE_SUGGESTIONS');
                $description = "";
                break;

            default:
                $hosts = HostHelper::suggestions($request);
                $title = tr('URL_TYPE_SUGGESTIONS');
                $description = "";
                break;
        
        }

        $data['title'] = $title;

        $data['description'] = $description;

        $data['data'] = $hosts;

        return $data;
    }

    /**
     * @method bookings_payment_by_stripe
     *
     * @uses stripe payment for booking
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param
     *
     * @return
     */
    
    public function bookings_payment_by_stripe($request, $booking_details) {

        try {

            DB::beginTransaction();

            // Check provider card details

            $card_details = UserCard::where('user_id', $request->id)->where('is_default', YES)->first();

            if (!$card_details) {

                throw new Exception(api_error(111), 111);
            }

            $customer_id = $card_details->customer_id;

            // Check stripe configuration
        
            $stripe_secret_key = Setting::get('stripe_secret_key');

            if(!$stripe_secret_key) {

                throw new Exception(api_error(107), 107);

            } 

            \Stripe\Stripe::setApiKey($stripe_secret_key);

            $total = $booking_details->total;

            $currency_code = Setting::get('currency_code', 'USD') ?: "USD";

            $charge_array = [
                                "amount" => round($total * 100),
                                "currency" => $currency_code,
                                "customer" => $customer_id,
                            ];

            $stripe_payment_response =  \Stripe\Charge::create($charge_array);

            $payment_id = $stripe_payment_response->id;

            $paid_amount = $stripe_payment_response->amount/100;

            $paid_status = $stripe_payment_response->paid;

            DB::commit();

            $booking_payment = new BookingPayment;

            $booking_payment->booking_id = $booking_details->id;

            $booking_payment->user_id = $booking_details->user_id;

            $booking_payment->provider_id = $booking_details->provider_id;

            $booking_payment->host_id = $booking_details->host_id;

            $booking_payment->payment_id = $payment_id;

            $booking_payment->payment_mode = CARD;

            $booking_payment->currency = Setting::get('currency', '$');

            $booking_payment->total_time = $booking_details->total_days;

            $booking_payment->time_price = $booking_details->total;

            $booking_payment->sub_total = $booking_payment->actual_total = $booking_payment->total = $booking_details->total;

            $booking_payment->paid_amount = $paid_amount;

            $booking_payment->paid_date = date('Y-m-d H:i:s');

            $booking_payment->admin_amount = 0.00;

            $booking_payment->provider_amount = 0.00;

            $booking_payment->status = PAID;

            $booking_payment->save();

            // Commission spilit for bookings

            $commission_details = booking_commission_spilit($booking_details->total);

            $booking_payment->admin_amount = $commission_details->admin_amount ?: 0.00;

            $booking_payment->provider_amount = $commission_details->provider_amount ?: 0.00;

            $booking_payment->save();


        } catch(Stripe_CardError | Stripe_InvalidRequestError | Stripe_AuthenticationError | Stripe_ApiConnectionError | Stripe_Error $e) {         

            DB::commit();

            return $this->sendError($e->getMessage(), $e->getCode());

        } catch(Exception $e) {

            // Something else happened, completely unrelated to Stripe

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());

        }
    }

    /**
     *
     * @method spaces_save()
     *
     * @uses used to get the common list details for hosts
     *
     * @created Vidhya R
     *
     * @updated Vidhya R
     *
     * @param 
     *
     * @return
     */

    public static function spaces_save($request) {

        try {
            
            $host_id = $request->space_id;

            if($host_id) {

                $host = Host::find($host_id);

                if(!$host) {
                    throw new Exception( api_error(200), 200);
                }

                $host_details = HostDetails::where('provider_id', $request->id)->where('host_id', $request->host_id)->first();

            } else {

                $host = new Host;

                $host->provider_id = $request->id;

                $host->save();

                $host_details = new HostDetails;
            
            }

            $host->host_type = $request->space_type ?: ($host->host_type ?: "");

            $host->host_name = $request->space_name ?: ($host->host_name ?: "");

            $host->description = $request->description ?: $host->description;


            $host->access_note = $request->access_note ?: $host->access_note;

            $host->access_method = $request->access_method ?: $host->access_method;
            
            $host->security_code = $request->security_code ?: $host->security_code;

            $host->host_owner_type = $request->space_owner_type ?: $host->host_owner_type;

            $host->total_spaces = $request->total_spaces ?: ($host->total_spaces ?: 1);

            $host->width_of_space = $request->width_of_space ?: $host->width_of_space;

            $host->height_of_space = $request->height_of_space ?: $host->height_of_space;
            
            $host->length_of_space = $request->length_of_space ?: $host->length_of_space;

            $host->is_automatic_booking = $request->is_automatic_booking ?? NO;

            $host->amenities = $request->amenities ?: $host->amenities;

            $host->dimension = ($host->width_of_space ?: 0)."Ft'W ".' *'.($host->length_of_space ?: 0)."Ft'L ".' *'.($host->height_of_space ?: 0)."Ft'H ";

            $host->save();

            /***** Host pictures upload ****/

            if($request->hasfile('picture')) {

                self::host_gallery_upload($request->file('picture'), $host->id, YES, $set_default_picture = YES);
            
            }

            /***** Host pictures upload ****/

            $host_details = $host_details ?: new HostDetails;

            $host_details->host_id = $host ? $host->id : 0;

            $host_details->provider_id = $request->id;

            $host_details->save();

            // Step2

            $host->street_details = $request->street_details ?: ($host->street_details ?: "");

            $host->country = $request->country ?: $host->country;

            $host->city = $request->city ?: ($host->city ?: "");

            $host->state = $request->state ?: ($host->state ?: "");

            $host->latitude = $request->latitude ?: ($host->latitude ?: 0.00);

            $host->longitude = $request->longitude ?: ($host->longitude ?: 0.00);

            $host->full_address = $request->full_address ?: ($host->full_address ?: "");

            $host->zipcode = $request->zipcode ?: ($host->zipcode ?: "");

            $host->service_location_id = $request->service_location_id ?: ($host->service_location_id ?: 0);

            $host->save();

            // Step 3 - Update Amenties details

            /*if($request->step == HOST_STEP_3) {

                $amenties = array_search_partial($request->all(), 'amenties_');

                foreach ((array) $amenties as $amenties_key => $amenties_value) {

                    // Check the already exists

                    $check_host_amenties = HostQuestionAnswer::where('host_id', $host->id)->where('common_question_id', $amenties_key)->first();

                    if(!$check_host_amenties) {

                        $host_amenties = new HostQuestionAnswer;

                        $host_amenties->provider_id = $request->id;

                        $host_amenties->host_id = $host->id;

                        $host_amenties->common_question_id = $amenties_key;

                        $host_amenties->common_question_answer_id = $amenties_value;

                        $host_amenties->save();

                    } else {

                        $check_host_amenties->common_question_answer_id = $amenties_value;

                        $check_host_amenties->save();
                    }

                }
            
            }*/

            // Step 5 & 6

            $host->checkin = $request->checkin ?: ($host->checkin ?: "");

            $host->checkout = $request->checkout ?: ($host->checkout ?: "");

            $host->min_days = $request->min_days ?: ($host->min_days ?: 0);

            $host->max_days = $request->max_days ?: ($host->max_days ?: 0);

            $host->base_price = $request->base_price ?: ($host->base_price ?: 0);


            $host->per_hour = $request->per_hour ?: ($host->per_hour ?: 0);

            $host->per_day = $request->per_day ?: ($host->per_day ?: 0);

            $host->per_month = $request->per_month ?: ($host->per_month ?: 0);

            $host->save();

            $response_array = ['success' => true, 'host_details' => $host_details, 'host' => $host];

            return $response_array;

        } catch(Exception $e) {

            $response_array = ['success' => false, 'error' => $e->getMessage(), 'error_code' => $e->getCode()];

            return $response_array;
        }
    
    }


    /**
     * @method host_availablity_list_update()
     *
     * @uses based on the provider data, add/remove the spaces
     *
     * @created Vithya R
     * 
     * @updated Vithya R
     *
     * @param datetime $from_date 
     *
     * @param datetime $to_date 
     *
     * @return boolean
     */
    
    public static function host_availablity_list_update($request, $host_details) {

        try {

            $from_date = date('Y-m-d H'.":00:00", strtotime($request->from_date));

            $to_date = date('Y-m-d H'.":00:00", strtotime($request->to_date));

            $period = new \DatePeriod(
                     new \DateTime($from_date),
                     new \DateInterval('PT1H'),
                     new \DateTime($to_date)
                );

            foreach ($period as $key => $value) {

                $current_date = $value->format('Y-m-d');

                $current_time = $value->format('H'.':00:00');

                // Check the host availability record

                $host_availablity = HostAvailability::where('date', $current_date)->where('time', $current_time)->where('host_id', $host_details->id)->first();

                if(!$host_availablity) {

                    $host_availablity = new HostAvailability;

                    $host_availablity->date = $current_date;

                    $host_availablity->time = $current_time;

                    $host_availablity->host_id = $host_details->id;

                    $host_availablity->total_spaces = $host_availablity->remaining_spaces = $host_details->total_spaces ?: 0;

                    $host_availablity->save();
                
                }

                $host_availablity->slot = get_time_slot($current_time);

                if($request->type == 1) {

                    $host_availablity->total_spaces += $request->spaces;
                    
                    $host_availablity->remaining_spaces += $request->spaces;

                } else {
                    
                    $host_availablity->total_spaces -= $request->spaces;

                    $host_availablity->remaining_spaces -= $request->spaces;

                    if($host_availablity->total_spaces < 0) {

                        $host_availablity->total_spaces = 0;
                    }

                    if($host_availablity->remaining_spaces < 0) {
                        
                        $host_availablity->remaining_spaces = 0;
                    }

                }

                $host_availablity->save();

            }

        } catch(Exception $e) {

            Log::info("host_availablity_update - error".print_r($e->getMessage(), true));

        }

    }
}