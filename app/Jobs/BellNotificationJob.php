<?php

namespace App\Jobs;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\Helpers\Helper;

use Log; 

use App\Setting;

use App\User;

use App\BellNotification;

use App\BellNotificationTemplate;

use App\Jobs\Job;

class BellNotificationJob extends Job implements ShouldQueue
{    
    use InteractsWithQueue, SerializesModels;

    protected $data;

    /**
    * The number of times the job may attempted.
    *
    * @var int 
    */
    public $tries =2;

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

            // Log::info('BellNotificationJob');

            $datas = $this->data;

            // Log::info($datas);
            
            $bell_notification_details = new BellNotification;

            $bell_notification_details->from_id = $datas['from_id'];

            $bell_notification_details->to_id = $datas['to_id'];

            $bell_notification_details->notification_type = $datas['notification_type'];

            $bell_notification_details->redirection_type = $datas['redirection_type'];

            $bell_notification_details->receiver = $datas['receiver_type'];

            $bell_notification_details->message = $datas['content'];

            $bell_notification_details->booking_id = $datas['booking_id'];

            $bell_notification_details->host_id = $datas['space_id'] ?? 0;
            
            $bell_notification_details->status = BELL_NOTIFICATION_STATUS_UNREAD;

            $bell_notification_details->save();
            
        } catch(Exception $e) {

            Log::info("BellNotificationJob - ERROR".print_r($e->getMessage(), true));
        }
        
    }
}
