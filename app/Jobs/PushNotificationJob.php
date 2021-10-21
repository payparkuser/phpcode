<?php

namespace App\Jobs;

use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Bus\Queueable;

use Exception;

use App\Mail\MailConfig;

use Log;

use App\Repositories\PushNotificationRepository as PushRepo;

class PushNotificationJob extends Job implements ShouldQueue {

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $device_token;
    protected $title;
    protected $content;
    protected $push_data;
    protected $device_type;
    protected $is_user;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($device_token, $title, $content, $push_data, $device_type, $is_user = YES) {

        $this->device_token = $device_token;
        $this->title = $title;
        $this->content = $content;
        $this->push_data = $push_data;
        $this->device_type = $device_type;
        $this->is_user = $is_user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle() {

        PushRepo::push_notification($this->device_token, $this->title, $this->content, $this->push_data, $this->device_type, $this->is_user);

    }
}
