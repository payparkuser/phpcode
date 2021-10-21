<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use App\Helpers\Helper;

class ServiceLocation extends Model
{
    /**
     * Get the bookings record associated with the user.
     */
    public function hosts() {
        
        return $this->hasMany(Host::class);
    }
   /**
     * Scope a query to only include active users.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCommonResponse($query) {

        return $query
            ->select(
                'service_locations.id as service_location_id',
                'service_locations.name as service_location_name',
                'service_locations.description as service_location_description',
                'service_locations.picture as service_location_picture'
            );
    
    }

    public static function boot() {

        parent::boot();

        static::deleting(function ($model){

            Helper::delete_file($model->picture , FILE_PATH_SERVICE_LOCATION);

            foreach ($model->hosts as $key => $host) {
                
                $host->delete();
                
            }

        });
    }
}
