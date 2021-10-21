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

use App\User;

class ProviderBookingsCheckInJob implements ShouldQueue
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

            $title = $content = Helper::push_message(611);

            $data['space_id'] = $booking_details['space_id'];

            $data['notification_type'] = BELL_NOTIFICATION_TYPE_BOOKING_PROVIDER_CHECKIN;

            $data['redirection_type'] = BELL_NOTIFICATION_REDIRECT_BOOKING_VIEW;

            $data['content'] = $content;

            $data['receiver_type'] = BELL_NOTIFICATION_RECEIVER_TYPE_USER;

            $data['booking_id'] = $booking_details->id;

            $data['from_id'] = $booking_details->provider_id;  

            $data['to_id'] = $booking_details->user_id;

            $data['type'] = YES;

            dispatch(new BellNotificationJob($data));

            $user_details = User::where('id', $booking_details->user_id)->VerifiedUser()->first();

            if (Setting::get('is_push_notification') == YES && $user_details) {
                
                if($user_details->push_notification_status == YES && ($user_details->device_token != '')) {

                    $push_data = ['type' => PUSH_NOTIFICATION_REDIRECT_BOOKING_VIEW, 'booking_id' => $booking_details->id];

                    // If user - YES, Provider - NO
                    fcm_config_update(YES);

                    \Notification::send($user_details->id, new \App\Notifications\PushNotification($title , $content, $push_data, $user_details->device_token));


                }
            
            }

            if(Setting::get('is_email_notification') == YES && $user_details) {

                $email_data['subject'] = Setting::get('site_name').'-'.tr('provider_checkin');

                $email_data['page'] = "emails.users.bookings.checkin";

                $data['frontend_url'] = Setting::get('frontend_url')."history-details/".$booking_details->id;

                $email_data['email'] = $user_details->email;

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
