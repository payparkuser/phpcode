<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Log, Auth;
use App\Jobs\Job;
use Setting, Exception;
use App\Helpers\Helper;
use App\Repositories\PushNotificationRepository as PushRepo;

use App\Provider;

class BookingsCheckInJob implements ShouldQueue
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

            $title = $content = Helper::push_message(606);

            $data['space_id'] = $booking_details->host_id;

            $data['notification_type'] = BELL_NOTIFICATION_TYPE_CHECKIN;

            $data['redirection_type'] = BELL_NOTIFICATION_REDIRECT_BOOKING_VIEW;

            $data['content'] = $content;

            $data['receiver_type'] = BELL_NOTIFICATION_RECEIVER_TYPE_PROVIDER;

            $data['booking_id'] = $booking_details->id;

            $data['from_id'] = $booking_details->user_id;

            $data['to_id'] = $booking_details->provider_id;

            $data['type'] = YES;

            dispatch(new BellNotificationJob($data));

            $provider_details = Provider::where('id', $booking_details->provider_id)->VerifiedProvider()->first();

            if (Setting::get('is_push_notification') == YES && $provider_details) {

                if($provider_details->push_notification_status == YES && ($provider_details->device_token != '')) {

                    $push_data = ['booking_id' => $booking_details->id, 'type' => PUSH_NOTIFICATION_REDIRECT_BOOKING_VIEW];

                    // If user - YES, Provider - NO
                    fcm_config_update(NO);

                    \Notification::send($provider_details->id, new \App\Notifications\PushNotification($title , $content, $push_data, $provider_details->device_token));

                }
            }

            if(Setting::get('is_email_notification') == YES && $provider_details) {

                $email_data['subject'] = Setting::get('site_name').'-'.tr('user_checkin');

                $email_data['page'] = "emails.users.bookings.checkin";

                $email_data['email'] = $provider_details->email;

                $data['frontend_url'] = Setting::get('frontend_url')."host/booking-details/".$booking_details->id;

                $data['booking_details'] = $booking_details->toArray();

                $data['host_details'] = $booking_details->hostDetails->toArray() ?? [];

                $data['timezone'] = $this->data['timezone'];
                
                $email_data['data'] = $data;

                dispatch(new SendEmailJob($email_data));

            }
            
        } catch(Exception $e) {

            Log::info("Error ".print_r($e->getMessage(), true));

        }
    }
}
