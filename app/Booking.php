<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use DB, Log;

class Booking extends Model
{

	/**
     * Get the user details associated with booking
     */
	public function userDetails() {

        return $this->belongsTo(User::class, 'user_id')->withDefault(['name' => "NO USER"]);

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
     * Get the booking chat details associated with booking
     */

	public function bookingChats() {

		return $this->hasMany(BookingChat::class, 'booking_id');

	}

	/**
     * Get the booking payments details associated with booking
     */

	public function bookingPayments() {

		return $this->hasOne(BookingPayment::class, 'booking_id')->withDefault();

	}

	/**
     * Get the booking provider reviews associated with booking
     */

	public function bookingProviderReviews() {

		return $this->hasMany(BookingProviderReview::class, 'booking_id');

	}

	/**
     * Get the booking user reviews associated with booking
     */

	public function bookingUserReviews() {

		return $this->hasMany(BookingUserReview::class);

	}

    /**
     * Get the Notification associated with booking
    */

    public function bookingNotifications() {

        return $this->hasMany(BellNotification::class, 'booking_id');

    }

    /**
     * Get the booking payments details associated with booking
     */

    public function bookingUserVehicle() {

        return $this->belongsTo(UserVehicle::class, 'user_vehicle_id');

    }

	/**
     * Scope a query to only include active users.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCommonResponse($query) {

        return $query->leftJoin('hosts', 'hosts.id', '=', 'bookings.host_id')
                    ->leftJoin('users', 'users.id', '=', 'bookings.user_id')
                    ->leftJoin('providers', 'providers.id', '=', 'bookings.provider_id')
                    ->leftJoin('booking_payments', 'booking_payments.booking_id', '=', 'bookings.id')
                    ->select(
                        'bookings.id as booking_id', 
                        'bookings.unique_id as booking_unique_id', 
                    	'bookings.user_id', 
                    	'hosts.provider_id', 
                        'hosts.id as space_id', 
                    	'hosts.host_name as space_name', 
                        'hosts.picture as space_picture', 
                    	'hosts.host_type as space_type', 'hosts.city as space_location',
                        'hosts.is_automatic_booking',
                        \DB::raw('IFNULL(users.name,"Deleted") as user_name'),
                        \DB::raw('IFNULL(users.picture,"") as user_picture'),
                        \DB::raw('IFNULL(providers.name,"Deleted") as provider_name'),
                        \DB::raw('IFNULL(providers.picture,"") as provider_picture'),
                        'bookings.checkin',
                        'bookings.checkout',
                        'bookings.duration',
                    	// 'bookings.total_guests',
                    	// 'bookings.total_days',
                    	'bookings.total',
                    	'bookings.status',
                        \DB::raw('IFNULL(booking_payments.payment_id,"") as payment_id')                       
                    );
    
    }

    /**
     * Scope a query to only include active users.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUserParkResponse($query) {

        return $query->leftJoin('hosts', 'hosts.id', '=', 'bookings.host_id')
                    ->select(
                        'bookings.id as booking_id', 
                        'bookings.user_id', 
                        'hosts.provider_id', 
                        'hosts.id as space_id', 
                        'hosts.host_name as space_name', 'hosts.picture as space_picture', 
                        'hosts.host_type as space_type', 'hosts.city as space_location', 
                        'hosts.is_automatic_booking',
                         DB::raw("DATE_FORMAT(bookings.checkin, '%d %b %Y') as checkin") ,
                         DB::raw("DATE_FORMAT(bookings.checkout, '%d %b %Y') as checkout") ,
                        // 'bookings.total_guests',
                        // 'bookings.total_days',
                        'bookings.total',
                        'bookings.status'
                    );
    
    }

    /**
     * Scope a query to only include active users.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeProviderBookingViewResponse($query) {

        return $query->leftJoin('hosts', 'hosts.id', '=', 'bookings.host_id')
                    ->select(
                        'bookings.id as id', 
                        'bookings.id as booking_id', 
                        'bookings.unique_id as booking_unique_id', 
                        'bookings.user_id',
                        'hosts.provider_id', 
                        \DB::raw('IFNULL(bookings.description,"") as booking_description'),
                        // 'adults', 'children', 'infants',
                        'hosts.id as space_id','hosts.host_name as space_name', 
                        'hosts.picture as space_picture', 
                        'hosts.host_type as space_type', 'hosts.city as space_location', 
                        'hosts.description as space_description',
                        'hosts.is_automatic_booking',
                        // 'bookings.total_guests',
                         // DB::raw("DATE_FORMAT(bookings.checkin, '%d %b %Y') as checkin") ,
                         // DB::raw("DATE_FORMAT(bookings.checkout, '%d %b %Y') as checkout") ,
                        // 'bookings.total_days',
                        'bookings.per_day',
                        // 'bookings.per_guest_price',
                        // 'bookings.total_additional_guest_price',
                        'bookings.currency',
                        'bookings.payment_mode',
                        'bookings.total',
                        'bookings.checkin',
                        'bookings.checkout',
                        'bookings.duration',
                        'bookings.status',
                        'bookings.price_type',
                        'bookings.user_vehicle_id',
                        DB::raw("DATE_FORMAT(bookings.cancelled_date, '%d %b %Y') as cancelled_date") ,
                        'cancelled_reason',
                        'bookings.created_at',
                        'bookings.updated_at', // @todo check and remove the values
                        DB::raw("DATE_FORMAT(bookings.created_at, '%d %b %Y') as created"),
                        DB::raw("DATE_FORMAT(bookings.updated_at, '%d %b %Y') as updated")
                    );
    
    }

    public static function boot() {

        parent::boot();

        static::creating(function ($model) {

            $unique_id = "0000000";

            $unique_id .= isset($model->attributes['id']) ? $model->attributes['id']: rand();;

            $model->attributes['unique_id'] = "B-".routefreestring($unique_id);

        });

        static::created(function ($model) {

            $unique_id = "0000000";

            $unique_id .= isset($model->attributes['id']) ? $model->attributes['id']: rand();;

            $model->attributes['unique_id'] = "B-".routefreestring($unique_id);

            $model->save();

        });

	    static::deleting(function ($model) {

            $model->hostDetails()->delete();

            $model->bookingChats()->delete();

            //We need booking payment details for calculating revenues, Don't Uncomment this line.
            
            // $model->bookingPayments()->delete();

            $model->bookingProviderReviews()->delete();

            $model->bookingUserReviews()->delete();

            foreach ($model->bookingNotifications as $key => $bell_notifiatios) {

                $bell_notifiatios->delete();
            
            }

	    });

	}

}
