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

use App\User;

use App\UserRefund;

class UserRefundJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

     protected $data;

    /**
    * The number of times jobs may attempted.
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
            
            $user_refund_details = $this->data['user_refund_details'];

            $title = $content = Helper::push_message(605);

           // Push Notification User Reviews
            $user_details = User::where('id', $user_refund_details->user_id)->VerifiedUser()->first();

            if (Setting::get('is_push_notification') == YES && $user_details) {

                $title = $content = Helper::push_message(621);

                if($user_details->push_notification_status == YES && ($user_details->device_token != '')) {

                    $push_data = ['user_refund_id' => $user_refund_details->id, 'type' => PUSH_NOTIFICATION_REDIRECT_HOME];

                    // If user - YES, Provider - NO
                    fcm_config_update(YES);
                   
                    \Notification::send($user_details->id, new \App\Notifications\PushNotification($title , $content, $push_data, $user_details->device_token));

                }
            }

            if (Setting::get('is_email_notification') == YES && $user_refund_details) {

                $user_details = User::find($user_refund_details->user_id);

                $email_data['subject'] = Setting::get('site_name').' '.tr('user_refund_success');

                $email_data['page'] = "emails.users.refund_payment";

                $email_data['email'] = $user_details->email;

                $data['refund_amount'] = $user_refund_details->amount;

                $data['user_details'] = $user_details->toArray();

                $data['timezone'] = $this->data['timezone'];

                $email_data['data'] = $data;
                
                dispatch(new SendEmailJob($email_data));

            }
          

        } catch(Exception $e) {

            Log::info("Error ".print_r($e->getMessage(), true));

        }
    }
}
