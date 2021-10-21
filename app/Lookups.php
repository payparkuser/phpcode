<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use App\Helpers\Helper;

class Lookups extends Model
{
    /**
     * Get the Approved Lookups details 
     */
    public function scopeApproved($query) {
        
        return $query->where('lookups.status' , APPROVED);	
    }    

    /**
     * Get the Approved Lookups details 
     */
    public function scopeareAmenities($query) {
        
        return $query->where('lookups.is_amenity' , YES);	
    }

    public static function boot() {

        parent::boot();

        static::deleting(function ($model){

            Helper::delete_file($model->picture , FILE_PATH_AMENITIES);

        });
    }
}
