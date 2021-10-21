<?php

namespace App\Repositories;

use App\Helpers\Helper;

use DB, Log, Validator, Exception, Setting;

use App\User;

class AccountRepository {

    /**
     *
     * @method user_delete_response()
     *
     * @uses used to delete user and relational tables
     *
     * @created Arun
     *
     * @updated 
     *
     * @param array
     *
     * @param integer
     *
     * @return
     */

    public static function user_delete_response($user) {

        try {
            
            $user->name = tr('deleted_user').$user->id;

            $user->email = 'deleted_user'.$user->id.tr('random_mail', str_random(5));

            $user->is_deleted = YES;

            if($user->save()) {

                Helper::delete_file($user->picture , PROFILE_PATH_USER);

                $user->userChatMessages()->delete();

                $user->userCards()->delete();

                foreach ($user->userBookings as $key => $booking_details) {
                    
                    $booking_details->delete();
                }

                $user->wishlists()->delete();

                $user->userBillingInfo()->delete();

                $user->userVehicle()->delete();

            }

            $response_array = ['success' => true, 'user_details' => $user];

            return $response_array;

        } catch(Exception $e) {

            $response_array = ['success' => false, 'error' => $e->getMessage(), 'error_code' => $e->getCode()];

            return $response_array;
        }

    } 

    /**
     *
     * @method provider_delete_response()
     *
     * @uses Provider to delete user and relational tables
     *
     * @created Arun
     *
     * @updated 
     *
     * @param array
     *
     * @param integer
     *
     * @return
     */

    public static function provider_delete_response($provider) {

        try {
            
            $provider->name = tr('deleted_provider').$provider->id;

            $provider->email = 'deleted_provider'.$provider->id.tr('random_mail', str_random(5));

            $provider->is_deleted = YES;

            if($provider->save()) {

                Helper::delete_file($provider->picture , PROFILE_PATH_PROVIDER);

                $provider->providerCards()->delete();

                $provider->providerDocuments()->delete();

                foreach ($provider->hosts as $key => $host_details) {

                    $host_details->delete();
                }

                $provider->providerCards()->delete();

                $provider->providerBillingInfo()->delete();

            }

            $response_array = ['success' => true, 'provider_details' => $provider];

            return $response_array;

        } catch(Exception $e) {

            $response_array = ['success' => false, 'error' => $e->getMessage(), 'error_code' => $e->getCode()];

            return $response_array;
        }

    } 


}