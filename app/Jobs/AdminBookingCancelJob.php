<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use Log, Auth;

use App\Jobs\Job;

use Setting, Exception;

use App\Helpers\Helper;

use App\Repositories\PushNotificationRepository as PushRepo;

use App\Provider;

class AdminBookingCancelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;
   /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 2;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {

            $booking_details = $this->data['booking_details'];

            $title = $content = Helper::push_message(622);

            $data['space_id'] = $booking_details['host_id'];

            $data['notification_type'] = BELL_NOTIFICATION_TYPE_BOOKING_CANCELLED_BY_ADMIN;

            $data['redirection_type'] = BELL_NOTIFICATION_REDIRECT_BOOKINGS;

            $data['content'] = $content;

            $data['receiver_type'] = BELL_NOTIFICATION_RECEIVER_TYPE_USER;

            $data['booking_id'] = $booking_details->id;

            $data['from_id'] = $booking_details->provider_id;  

            $data['to_id'] = $booking_details->user_id;

            $data['type'] = YES;

            Log::info($data);

            dispatch(new BellNotificationJob($data));

            $provider_details = Provider::where('id', $booking_details->provider_id)->VerifiedProvider()->first();

            $user_details = User::where('id', $booking_details->user_id)->VerifiedUser()->first();

            // Push Notification for Provider

            if (Setting::get('is_push_notification') == YES && $provider_details) {

                $title = $content = Helper::push_message(604);

                if($provider_details->push_notification_status == YES && ($provider_details->device_token != '')) {

                    $push_data = ['type' => PUSH_NOTIFICATION_REDIRECT_BOOKINGS, 'booking_id' => $booking_details->id];

                    // If user - YES, Provider - NO
                    fcm_config_update(NO);
                   
                    \Notification::send($provider_details->id, new \App\Notifications\PushNotification($title , $content, $push_data, $provider_details->device_token));

                }
            }

            // Push Notification for User

            if (Setting::get('is_push_notification') == YES && $user_details) {

                if($user_details->push_notification_status == YES && ($user_details->device_token != '')) {

                    $push_data = ['type' => PUSH_NOTIFICATION_REDIRECT_BOOKINGS, 'booking_id' => $booking_details->id];

                    // If user - YES, Provider - NO
                    fcm_config_update(YES);

                    \Notification::send($user_details->id, new \App\Notifications\PushNotification($title , $content, $push_data, $user_details->device_token));

                }
            }
            
            // Email Notification for provider

            if(Setting::get('is_email_notification') == YES && $provider_details) {

                $email_data['subject'] = Setting::get('site_name').'-'.tr('user_cancel_booking_subject', $booking_details->unique_id);

                $email_data['page'] = "emails.providers.bookings.cancel";

                $email_data['email'] = $provider_details->email;

                $data['frontend_url'] = Setting::get('frontend_url')."host/booking-details/".$booking_details->id;

                $data['booking_details'] = $booking_details->toArray();
                
                $data['host_details'] = $booking_details->hostDetails->toArray() ?? [];

                $data['timezone'] = $this->data['timezone'];

                $email_data['data'] = $data;

                dispatch(new SendEmailJob($email_data));
                                    
            }

            // Email Notification for User

            if(Setting::get('is_email_notification') == YES && $user_details) {

                $email_data['subject'] = Setting::get('site_name').'-'.tr('provider_cancel_booking_subject', $booking_details->unique_id);
              
                $email_data['page'] = "emails.users.bookings.cancel";

                $email_data['email'] = $user_details->email;

                $data['frontend_url'] = Setting::get('frontend_url')."history-details/".$booking_details->id;

                $data['booking_details'] = $booking_details->toArray();

                $data['space_details'] = $booking_details->spaceDetails->toArray() ?? [];

                $data['timezone'] = $this->data['timezone'];
                
                $email_data['data'] = $data;

                dispatch(new SendEmailJob($email_data));
            }

        } catch(Exception $e) {

                Log::info("Error -".print_r($e->getMessage(),true));
        }
    }
}



