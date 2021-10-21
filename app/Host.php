<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use App\Helpers\Helper;

class Host extends Model
{

    public function scopeVerifedHostQuery($query) {

        return $query->where('hosts.status' , SPACE_OWNER_PUBLISHED)
            ->where('hosts.admin_status' , ADMIN_SPACE_APPROVED)
            ->where('hosts.is_admin_verified' , ADMIN_SPACE_VERIFIED);
   
    }

    /**
     * Scope a query to only include active users.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUserParkResponse($query) {

        $currency = \Setting::get('currency' , '$');

        return $query->VerifedHostQuery()
            ->leftJoin('providers','providers.id' ,'=' , 'hosts.provider_id')
            ->select(
            'hosts.id as space_id',
            'hosts.id as host_id',
            'hosts.unique_id as space_unique_id',
            'hosts.host_name as space_name',
            'hosts.picture as space_picture',
            'hosts.host_type as space_type',
            'hosts.overall_ratings',
            'hosts.total_ratings',
            'hosts.provider_id as provider_id',
            'hosts.city as space_location',
            'hosts.latitude',
            'hosts.longitude',
            'hosts.is_automatic_booking as is_automatic_booking',
            'hosts.per_hour as per_hour',
            \DB::raw("'$currency' as currency")
            );
    
    }

        /**
     * Scope a query to only include active users.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUserParkFullResponse($query) {

        $currency = \Setting::get('currency' , '$');

        return $query->leftJoin('providers','providers.id' ,'=' , 'hosts.provider_id')
            ->select(
            'hosts.id as space_id',
            'hosts.id as host_id',
            'hosts.unique_id as space_unique_id',
            'hosts.host_name as space_name',
            'hosts.description as space_description',
            'hosts.picture as space_picture',
            'hosts.overall_ratings',
            'hosts.total_ratings',
            'hosts.total_spaces as total_spaces',
            'hosts.host_type as space_type',

            'hosts.access_note as access_note',
            'hosts.access_method as access_method',
            'hosts.host_owner_type as host_owner_type',
            'hosts.host_owner_type as space_owner_type',

            'hosts.provider_id as provider_id',
            \DB::raw('IFNULL(providers.name,"") as provider_name'),
             \DB::raw('IFNULL(providers.picture,"") as provider_picture'),

            'hosts.amenities',

            'hosts.city as space_location',
            'hosts.latitude',
            'hosts.longitude',
            'hosts.per_hour as per_hour',
            'hosts.per_day as per_day',
            'hosts.per_month as per_month',
            'hosts.is_automatic_booking as is_automatic_booking',

            \DB::raw('IFNULL(hosts.width_of_space,0) as width_of_space'),
            \DB::raw('IFNULL(hosts.height_of_space,0) as height_of_space'),
            \DB::raw('IFNULL(hosts.length_of_space,0) as length_of_space'),
                
            \DB::raw('IFNULL(hosts.dimension,0) as dimension'),
            
            \DB::raw("'$currency' as currency"),
            \DB::raw("DATE_FORMAT(hosts.created_at, '%M %Y') as created") ,
            \DB::raw("DATE_FORMAT(hosts.updated_at, '%M %Y') as updated") 
            );
    
    }

    /**
     * Scope a query to only include active users.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeProviderParkFullResponse($query) {

        $currency = \Setting::get('currency' , '$');

        return $query->leftJoin('providers','providers.id' ,'=' , 'hosts.provider_id')
            ->select(
            'hosts.id as space_id',
            'hosts.id as host_id',
            'hosts.unique_id as space_unique_id',
            'hosts.host_name as space_name',
            'hosts.description as space_description',
            'hosts.picture as space_picture',
            'hosts.host_type as space_type',
            'hosts.available_days',
            
            'hosts.overall_ratings',
            'hosts.total_ratings',
            'hosts.total_spaces as total_spaces',
            'hosts.amenities as amenities',

            'hosts.access_note as access_note',
            'hosts.access_method as access_method',
            'hosts.security_code as security_code',
            'hosts.host_owner_type as host_owner_type',
            'hosts.host_owner_type as space_owner_type',
            'hosts.provider_id as provider_id',

            \DB::raw('IFNULL(providers.name,"") as provider_name'),
            \DB::raw('IFNULL(providers.picture,"") as provider_picture'),
            'hosts.service_location_id',
            'hosts.latitude',
            'hosts.longitude',
            'hosts.full_address',
            'hosts.street_details',
            'hosts.city',
            'hosts.state',
            'hosts.country',
            'hosts.zipcode',

            \DB::raw('IFNULL(hosts.width_of_space,0) as width_of_space'),
            \DB::raw('IFNULL(hosts.height_of_space,0) as height_of_space'),
            \DB::raw('IFNULL(hosts.length_of_space,0) as length_of_space'),
                
            \DB::raw('IFNULL(hosts.dimension,0) as dimension'),

            'hosts.per_hour as per_hour',
            'hosts.per_day as per_day',
            'hosts.per_month as per_month',
            'hosts.is_automatic_booking as is_automatic_booking',
            \DB::raw("'$currency' as currency"),
            \DB::raw("DATE_FORMAT(hosts.created_at, '%M %Y') as created") ,
            \DB::raw("DATE_FORMAT(hosts.updated_at, '%M %Y') as updated") 
            );
    
    }

    /**
     * Scope a query to only include active users.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUserBaseResponse($query) {

        $currency = \Setting::get('currency' , '$');

        return $query->where('hosts.status' , SPACE_OWNER_PUBLISHED)
            ->where('hosts.admin_status' , ADMIN_SPACE_APPROVED)
            ->where('hosts.is_admin_verified' , ADMIN_SPACE_VERIFIED)
            ->leftJoin('providers','providers.id' ,'=' , 'hosts.provider_id')
            ->select(
            'hosts.id as space_id',
            'hosts.id as host_id',
            'hosts.unique_id as space_unique_id',
            'hosts.host_name as space_name',
            'hosts.picture as space_picture',
            'hosts.overall_ratings',
            'hosts.total_ratings',
            'hosts.provider_id as provider_id',
            'hosts.service_location_id',
            'hosts.city as space_location',
            'hosts.latitude',
            'hosts.longitude',
            'hosts.base_price as base_price',
            'hosts.per_day as per_day',
            'hosts.per_hour as per_hour',
            'hosts.is_automatic_booking',
            \DB::raw("'$currency' as currency")
            );
    
    }
    
    public function hostAvailabilities() {
        return $this->hasMany(HostAvailability::class, 'host_id');
    } 

    // Need to Discuss - hasOne
    public function hostDetails() {
        return $this->hasMany(HostDetails::class, 'host_id');
    } 

    public function hostGalleries() {
        return $this->hasMany(HostGallery::class, 'host_id');
    }

    public function hostInventories() {
        return $this->hasMany(HostInventory::class, 'host_id');
    }

    public function hostWishlist() {
        return $this->hasMany(Wishlist::class, 'host_id');
    }
 
    /**
     * Get the booking record associated with the host.
     */
    public function bookings() {
        return $this->hasMany(Booking::class, 'host_id');
    } 

    /**
     * Get the booking user review record associated with the host.
     */
    public function bookingUserReviews() {
        return $this->hasMany(BookingUserReview::class, 'host_id');
    }

    /**
     * Get the Notification associated with booking
     */

    public function spaceNotifications() {

        return $this->hasMany(BellNotification::class, 'host_id');

    }

    /**
     * Get the booking payments record associated with the host.
     */
    public function bookingPayments() {
        
        return $this->hasMany(BookingPayment::class, 'host_id');
    }
    
    /**
     * Get the provider record associated with the host.
     */
    public function providerDetails() {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    /**
     * Get the serviceLocation record associated with the host.
     */
    public function serviceLocationDetails() {
        return $this->belongsTo(ServiceLocation::class, 'service_location_id');
    }

    /**
     * Get the wishlists record associated with the host.
     */
    public function wishlists() {
        return $this->hasMany(Wishlist::class, 'host_id');
    }

    public static function boot() {

        parent::boot();

        static::creating(function ($model) {

            $model->attributes['unique_id'] = routefreestring(isset($model->attributes['host_name']) ? $model->attributes['host_name'] : uniqid());
        });

        static::updating(function($model) {

            $model->attributes['unique_id'] = routefreestring(isset($model->attributes['host_name']) ? $model->attributes['host_name'] : uniqid());

        });

        static::deleting(function($model) {

            $model->hostAvailabilities()->delete();

            Helper::delete_file($model->picture , FILE_PATH_HOST);

            foreach ($model->hostGalleries as $key => $host_gallery_details) {

                Helper::delete_file($host_gallery_details->picture , FILE_PATH_HOST);

                $host_gallery_details->delete();
            
            }

            $model->hostDetails()->delete();

            foreach ($model->bookingUserReviews as $key => $booking_reviews) {
                
                $booking_reviews->delete();
            }
            
            $model->wishlists()->delete();

            $model->hostInventories()->delete();

            foreach ($model->bookings as $key => $booking_details) {
                
                $booking_details->delete();
                
            }

            $model->hostWishlist()->delete();

            foreach ($model->spaceNotifications as $key => $bell_notifiatios) {

                $bell_notifiatios->delete();
            
            }

        });
    }
}
