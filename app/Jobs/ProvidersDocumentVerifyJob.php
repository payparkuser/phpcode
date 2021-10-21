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

class ProvidersDocumentVerifyJob implements ShouldQueue
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

            $title = $content = Helper::push_message(617);

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

                $email_data['subject'] = Setting::get('site_name').' -'.tr('provider_documents_verify_subject');

                $email_data['page'] = "emails.providers.document_verify";

                $email_data['email'] = $provider_details->email;

                dispatch(new SendEmailJob($email_data));
            }     

        } catch(Exception $e) {

            Log::info("Error ".print_r($e->getMessage(), true));

        }
    }
}
