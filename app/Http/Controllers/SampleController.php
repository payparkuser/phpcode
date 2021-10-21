<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\User, App\Booking;

use Setting, Log;

class SampleController extends Controller
{
    public function user_chat() {

    	return view('sample.user_chat')->with('user_id', 7)->with('provider_id', 1)->with('request_id', 1);
    }

    public function provider_chat() {

    	return view('sample.provider_chat')->with('user_id', 7)->with('provider_id', 1)->with('request_id', 1);
    }

    public function email_testing() {

        $user_details = User::first();

        $booking_details = Booking::orderBy('id', 'desc')->first();

        $email_data['subject'] = Setting::get('site_name').' - '.tr('email_bookings_invoice_subject', $booking_details->unique_id);

        $email_data['page'] = "emails.users.bookings.invoice";

        $email_data['email'] = $user_details->email;

        $data['frontend_url'] = Setting::get('frontend_url')."history-details/".$booking_details->id;

        $data['booking_details'] = $booking_details->toArray();
        
        $data['host_details'] = $booking_details->hostDetails->toArray() ?? [];

        $email_data['data'] = $data;

        return view($email_data['page'])->with('data', $email_data);
    }

    /**
     *
     * @method 
     *
     * @uses
     *
     * @created
     *
     * @updated
     *
     * @param
     *
     * @return
     * 
     */

    public function name() {

    	try {

    		$validator = Validator::make($request->all(), [

            ]);

            if($validator->fails()) {

                $error = implode(',', $validator->messages()->all());

                throw new Exception($error , 101);

            }

            DB::beginTransaction();

            DB::commit();

    		return $this->sendResponse($message, $code, $data);

    	} catch(Exception $e) {

    		DB::rollback();

    		return $this->sendError($e->getMessage(), $e->getCode());

    	}
    
    }
}
