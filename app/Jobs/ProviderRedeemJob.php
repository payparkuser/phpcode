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

use App\ProviderRedeem;

class ProviderRedeemJob implements ShouldQueue
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
            
            $provider_redeem_details = $this->data['provider_redeem_details'];

            $title = $content = Helper::push_message(605);

           // Push Notification User Reviews
            $provider_details = Provider::where('id', $provider_redeem_details->provider_id)->VerifiedProvider()->first();

            if (Setting::get('is_push_notification') == YES && $provider_details) {

                $title = $content = Helper::push_message(621);

                if($provider_details->push_notification_status == YES && ($provider_details->device_token != '')) {

                    $push_data = ['provider_redeems_id' => $provider_redeem_details->id, 'type' => PUSH_NOTIFICATION_REDIRECT_HOME];

                    // If user - YES, Provider - NO
                    fcm_config_update(NO);
                   
                    \Notification::send($provider_redeem_details->id, new \App\Notifications\PushNotification($title , $content, $push_data, $provider_details->device_token));

                }
            }

            if (Setting::get('is_email_notification') == YES && $provider_redeem_details) {

                $provider_details = Provider::find($provider_redeem_details->user_id);

                $email_data['subject'] = Setting::get('site_name').' '.tr('redeem_payment_success');

                $email_data['page'] = "emails.providers.redeem_payment";

                $email_data['email'] = $provider_details->email;

                $data['redeem_amount'] = $request->amount;

                $data['provider_details'] = $provider_details->toArray();

                $data['timezone'] = $this->data['timezone'];

                $email_data['data'] = $data;
                
                dispatch(new SendEmailJob($email_data));

            }
          

        } catch(Exception $e) {

            Log::info("Error ".print_r($e->getMessage(), true));

        }
    }
}
