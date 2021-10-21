<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

use Log, PDF;

class HostMail extends Mailable
{
    use Queueable, SerializesModels;
    
    public $data;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {          
        if(isset($this->data['is_pdf_attached'])) {
            $pdf = PDF::loadView($this->data['file_path'], $this->data['report_data']);  

            return $this->view($this->data['page'])
                    ->to($this->data['email'])
                    ->subject($this->data['subject'])
                    ->attachData($pdf->output(), $this->data['file_name'].".pdf")
                    ->with([
                        'email_data' => $this->data
                    ]);                     
      
        } else {

            return $this->view($this->data['page'])
                    ->to($this->data['email'])
                    ->subject($this->data['subject'])
                    ->with([
                        'email_data' => $this->data
                    ]);
        }
        
    }
}
