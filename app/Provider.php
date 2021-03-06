<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Setting, DB, Log;

use App\Helpers\Helper;

class Provider extends Model
{

    /**
     * Scope a query to only include active users.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVerifiedProvider($query) {

        return $query->where('providers.is_verified', PROVIDER_EMAIL_VERIFIED)->where('providers.status', PROVIDER_APPROVED);

    }

    /**
     * Scope a query to only include active users.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIsNotDeleted($query) {

        return $query->where('providers.is_deleted', NO);

    }

	/**
     * Scope a query to only include active users.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCommonResponse($query) {

        $is_appstore_updated = \Setting::get('is_appstore_updated' , NO);

        $identity_verification_preview = \Setting::get('identity_verification_preview', envfile('APP_URL')."/verification-placeholder.jpg");

        return $query->select(
            'providers.id as provider_id',
            'providers.username as username',
            'providers.name',
            'providers.email as email',
            'providers.picture as picture',
            'providers.mobile as mobile',
            \DB::raw('IFNULL(providers.description,"") as description'),
            'providers.token as token',
            'providers.token_expiry as token_expiry',
            'providers.social_unique_id as social_unique_id',
            'providers.login_by as login_by',
            'providers.device_type as device_type',
            'providers.payment_mode',
            'providers.provider_card_id',
            'providers.status as provider_status',
            'providers.email_notification_status',
            'providers.push_notification_status',
            'providers.is_verified',
            'providers.provider_type',
            'providers.registration_steps',
            'providers.is_document_verified',
            'providers.created_at',
            'providers.updated_at',
            'providers.timezone',
            \DB::raw("'$is_appstore_updated' as is_appstore_updated"),
            \DB::raw("'$identity_verification_preview' as identity_verification_preview"),
            'providers.identity_verification_file'
            );
    
    }

    /**
     * Scope a query to only include active users.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFullResponse($query) {

        $is_appstore_updated = \Setting::get('is_appstore_updated' , NO);

        $identity_verification_preview = \Setting::get('identity_verification_preview', envfile('APP_URL')."/verification-placeholder.jpg");

        return $query->select(
            'providers.id as provider_id',
            'providers.username as username',
            'providers.name',
            'providers.name as provider_name',
            'providers.email as email',
            'providers.picture as picture',
            'providers.mobile as mobile',
            \DB::raw('IFNULL(providers.description,"") as description'),
            \DB::raw('IFNULL(providers.full_address,"") as full_address'),
            'providers.work',
            'providers.school',
            'providers.languages',
            'providers.response_rate',
            'providers.token as token',
            'providers.social_unique_id as social_unique_id',
            'providers.login_by as login_by',
            'providers.payment_mode',
            'providers.provider_card_id',
            'providers.status as provider_status',
            'providers.email_notification_status',
            'providers.push_notification_status',
            'providers.is_verified',
            'providers.provider_type',
            'providers.registration_steps',
            DB::raw("DATE_FORMAT(providers.created_at, '%M %Y') as joined") ,
            'providers.created_at',
            'providers.updated_at',
            \DB::raw("'$is_appstore_updated' as is_appstore_updated"),
            \DB::raw("'$identity_verification_preview' as identity_verification_preview"),
            'providers.identity_verification_file'

            );
    
    }

    /**
     * Get the cards record associated with the provider.
     */
    public function providerCards() {
        
        return $this->hasMany(ProviderCard::class, 'provider_id');
    }
    
    /**
     * Get the cards record associated with the provider.
     */
    public function providerDocuments() {
        
        return $this->hasMany(ProviderDocument::class, 'provider_id');
    }

    public function hosts() {

        return $this->hasMany(Host::class, 'provider_id');

    }

    /**
     * Get the Booking Payments record associated with provider.
     */
    public function bookingPayments() {
        
        return $this->hasMany(BookingPayment::class);
    }

    public function providerSubscriptionPayment() {
   
        return $this->hasMany(ProviderSubscriptionPayment::class, 'provider_id');
   
    }

    public function providerBillingInfo() {
        
        return $this->hasMany(ProviderBillingInfo::class, 'provider_id');
    }

    public static function boot() {

        parent::boot();

        static::creating(function ($model) {

            $model->attributes['first_name'] = $model->attributes['last_name'] = $model->attributes['name'];

            $model->attributes['is_verified'] = PROVIDER_EMAIL_VERIFIED;

            if (Setting::get('is_account_email_verification') == YES && envfile('MAIL_USERNAME') && envfile('MAIL_PASSWORD')) { 

                if($model->login_by == 'manual') {

                    $model->generateEmailCode();

                }

            }

            $model->attributes['status'] = PROVIDER_PENDING;

            $model->attributes['payment_mode'] = COD;

            $model->attributes['username'] = routefreestring($model->attributes['name']);

            $model->attributes['unique_id'] = uniqid();

            if(in_array($model->login_by, ['facebook' , 'google'])) {
                
                $model->attributes['password'] = \Hash::make($model->attributes['social_unique_id']);
            }

        });

        static::created(function($model) {

            $model->attributes['email_notification_status'] = $model->attributes['push_notification_status'] = YES;

            $model->attributes['unique_id'] = "UID"."-".$model->attributes['id']."-".uniqid();

            $model->attributes['token'] = Helper::generate_token();

            $model->attributes['token_expiry'] = Helper::generate_token_expiry();

            $model->save();
       
        });

        static::updating(function($model) {

            $model->attributes['username'] = routefreestring($model->attributes['name']);

            $model->attributes['first_name'] = $model->attributes['last_name'] = $model->attributes['name'];

        });

        static::deleting(function ($model) {

            Helper::delete_file($model->picture , PROFILE_PATH_PROVIDER);

            $model->providerCards()->delete();

            $model->providerDocuments()->delete();

            foreach ($model->hosts as $key => $host_details) {

                $host_details->delete();
            }

            $model->providerSubscriptionPayment()->delete();

            $model->providerCards()->delete();

            $model->providerBillingInfo()->delete();

            $model->hosts()->delete();

            $model->bookingPayments()->delete();

        });

    }

    /**
     * Generates Token and Token Expiry
     * 
     * @return bool returns true if successful. false on failure.
     */

    protected function generateEmailCode() {

        $this->attributes['verification_code'] = Helper::generate_email_code();

        $this->attributes['verification_code_expiry'] = Helper::generate_email_expiry();

        $this->attributes['is_verified'] = 0;

        return true;
    
    }
}
