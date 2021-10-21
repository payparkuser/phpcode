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

class ProviderStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;

    /**
    * the number of times job may attempted      
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
            
            $provider_details = $this->data['provider_details'];

            if($this->data['email_verification'] == YES) {

                $content_code = $provider_details->is_verified ? 620 : 621;

                $title = $content = Helper::push_message($content_code);

                $subject = $provider_details->is_verified ? tr('provider_email_verified') : tr('provider_email_declined');

                $text_status = $provider_details->is_verified ? tr('verified') : tr('declined');

            } else {

                $content_code = $provider_details->status ? 618 : 619;

                $title = $content = Helper::push_message($content_code);

                $subject = $provider_details->status ? tr('provider_approved') : tr('provider_declined');

                $text_status = $provider_details->status ? tr('approved') : tr('declined');

            }

            if (Setting::get('is_push_notification') == YES) {

                if($provider_details->push_notification_status == YES && $provider_details->device_token != '') {

                    $push_data = ['type' => PUSH_NOTIFICATION_REDIRECT_HOME];

                    // If user - YES, Provider - NO
                    fcm_config_update(NO);
                   
                    \Notification::send($provider_details->id, new \App\Notifications\PushNotification($title , $content, $push_data, $provider_details->device_token));

                }
            }
            
            //Email Notification for Provider-  Space Declined
            if (Setting::get('is_email_notification') == YES) {

                $email_data['subject'] = Setting::get('site_name').' - '.$subject;

                $email_data['page'] = "emails.providers.provider_status";

                $email_data['email'] = $provider_details->email;

                $email_data['status'] = $text_status;

                dispatch(new SendEmailJob($email_data));
            }     

        } catch(Exception $e) {

            Log::info("Error ".print_r($e->getMessage(), true));

        }
    }
}
