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

class SpaceDeclineJob implements ShouldQueue
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
            
            $space_details = $this->data['space_details'];

            $title = $content = Helper::push_message(609);

            $data['space_id'] = $space_details->id;

            $data['notification_type'] = BELL_NOTIFICATION_TYPE_SPACE_DECLINED;

            $data['redirection_type'] = BELL_NOTIFICATION_REDIRECT_SPACE_VIEW;
            
            $data['content'] = $content;

            $data['receiver_type'] = BELL_NOTIFICATION_RECEIVER_TYPE_PROVIDER;

            $data['booking_id'] = 0;

            $data['from_id'] = $this->data['admin_id'];
            
            $data['to_id'] = $space_details->provider_id;

            $data['type'] = YES;
           
            dispatch(new BellNotificationJob($data));
        
            if (Setting::get('is_push_notification') == YES) {

                if($provider_details = Provider::where('id',$space_details->provider_id)->where('push_notification_status' , YES)->where("device_token", "!=", "")->first()) {

                    $push_data = ['type' => PUSH_NOTIFICATION_REDIRECT_SPACE_VIEW, 'space_id' => $space_details->id];

                    // If user - YES, Provider - NO
                    fcm_config_update(NO);
                    
                    \Notification::send($provider_details->id, new \App\Notifications\PushNotification($title , $content, $push_data, $provider_details->device_token));

                }
            }

            //Email Notification for Provider-  Space Declined
            if (Setting::get('is_email_notification') == YES) {

                $provider_details = Provider::find($space_details->provider_id);

                $email_data['subject'] = Setting::get('site_name').' '.tr('host_decline_title');

                $email_data['page'] = "emails.providers.host";

                $email_data['email'] = $provider_details->email;

                $data['host_details'] = $space_details->toArray();

                $data['url'] = Setting::get('frontend_url')."space/single/".$space_details['id'];
        
                $data['status'] = tr('declined');

                $data['timezone'] = $this->data['timezone'];
                
                $email_data['data'] = $data;

                dispatch(new SendEmailJob($email_data));
            }

        } catch(Exception $e) {

            Log::info("Error ".print_r($e->getMessage(), true));

        }
    }
}
