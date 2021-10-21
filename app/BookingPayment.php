<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BookingPayment extends Model
{

    /**
     * Get the user details associated with booking
     */
    public function userDetails() {

        return $this->belongsTo(User::class, 'user_id');

    }

    /**
     * Get the provider details associated with booking
     */
    public function providerDetails() {

        return $this->belongsTo(Provider::class, 'provider_id');

    }

    /**
     * Get the host details associated with booking
     */
    public function hostDetails() {

        return $this->belongsTo(Host::class, 'host_id');

    }  
    /**
     * Get the booking details associated with booking
     */
    public function bookingDetails() {

        return $this->belongsTo(Booking::class, 'booking_id');

    }  

    public function scopeBookingpaymentdetails($query){

    	return $query->leftjoin('bookings','bookings.id','=','booking_payments.booking_id')
            ->leftjoin('users','users.id','=','booking_payments.user_id')
            ->leftjoin('providers','providers.id','=','booking_payments.provider_id')
            ->leftjoin('hosts','hosts.id','=','booking_payments.host_id')
            ->select('booking_payments.*',
                'users.id as user_id','users.name as user_name',
                'providers.id as provider_id','providers.name as provider_name',
                'hosts.id as space_id','hosts.host_name as space_name')
            ->orderby('booking_payments.updated_at','desc' );
    }

    public function scopeProviderDashboardHighlights($query){

        return $query->leftjoin('bookings','bookings.id','=','booking_payments.booking_id')
            ->leftjoin('users','users.id','=','booking_payments.user_id')
            ->leftjoin('providers','providers.id','=','booking_payments.provider_id')
            ->leftjoin('hosts','hosts.id','=','booking_payments.host_id')
            ->select(
                'booking_payments.booking_id',
                'bookings.unique_id as booking_unique_id',
                'users.id as user_id',
                'users.name as user_name',
                'users.picture as user_picture',
                'hosts.id as space_id',
                'hosts.host_name as space_name',
                'booking_payments.provider_amount',
                'booking_payments.paid_date'

            )
            ->orderby('booking_payments.updated_at','desc' );
    }

    public function scopeBookingpaymentdetailsview($query){

        return $query->leftjoin('bookings','bookings.id','=','booking_payments.booking_id')
                    ->leftjoin('users','users.id','=','booking_payments.user_id')
                    ->leftjoin('providers','providers.id','=','booking_payments.provider_id')
                    ->leftjoin('hosts','hosts.id','=','booking_payments.host_id')
                    ->select('booking_payments.*',
                        'users.id as user_id','users.name as user_name',
                        'providers.id as provider_id','providers.name as provider_name',
                        'hosts.id as space_id','hosts.host_name as space_name',
                        'hosts.description as space_description'
                    )
                    ->orderby('booking_payments.updated_at','desc' );
    } 
}
