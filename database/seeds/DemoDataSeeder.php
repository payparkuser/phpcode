<?php

use Illuminate\Database\Seeder;

use App\Helpers\Helper;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        /**
         * @uses Used to add demo admin details
         *
         * @created Vidhya 
         *
         * @updated Vidhya
         */
        
        if(Schema::hasTable('admins')) {

            $check_admin_details = DB::table('admins')->where('email' , 'admin@rentcubo.com')->count();

            if(!$check_admin_details) {

                DB::table('admins')->insert([
                    [
                        'name' => 'Admin',
                        'email' => 'admin@rentcubo.com',
                        'password' => \Hash::make('123456'),
                        'picture' => envfile('APP_URL')."/placeholder.jpg",
                        'status' => 1,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                ]);

            }

            $check_test_admin_details = DB::table('admins')->where('email' , 'test@rentcubo.com')->count();

            if(!$check_test_admin_details) {

                DB::table('admins')->insert([

                    [
                        'name' => 'Test',
                        'email' => 'test@rentcubo.com',
                        'password' => \Hash::make('123456'),
                        'picture' => envfile('APP_URL')."/placeholder.jpg",
                        'status' => 1,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ],
                ]);
            }
        }

        /**
         * @uses Used to add demo user details
         *
         * @created Vidhya 
         *
         * @updated Vidhya
         */

        if(Schema::hasTable('users')) {

            $check_user_details = DB::table('users')->where('email' , 'user@rentcubo.com')->count();

            if(!$check_user_details) {

                $user_details = DB::table('users')->insert([
                    [
                        'unique_id' => uniqid(),
                        'username' => 'userdemo',
                        'name' => 'User',
                        'first_name' => 'User',
                        'last_name' => 'User',
                        'email' => 'user@rentcubo.com',
                        'password' => \Hash::make('123456'),
                        'picture' =>"https://admin-rentroom.rentcubo.info/placeholder.jpg",
                        'login_by' =>"manual",
                        'device_type' => "web",
                        'status' => USER_APPROVED,
                        'is_verified' => USER_EMAIL_VERIFIED,
                        'user_type'=>0,
                        'payment_mode' => COD,
                        'language_id' => 1,
                        'registration_steps' => 1,
                        'token' => Helper::generate_token(),
                        'token_expiry' => Helper::generate_token_expiry(),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ],
                ]);

            }

            $check_test_details = DB::table('users')->where('email' , 'test@rentcubo.com')->count();

            if(!$check_test_details) {

                $test_details = DB::table('users')->insert([
                    [
                        'unique_id' => uniqid(),
                        'username' => 'Test',
                        'name' => 'Test',
                        'first_name' => 'TEST',
                        'last_name' => 'TEST',
                        'email' => 'test@rentcubo.com',
                        'password' => \Hash::make('123456'),
                        'picture' =>"https://admin-rentroom.rentcubo.info/placeholder.jpg",
                        'login_by' =>"manual",
                        'device_type' => "web",
                        'status' => USER_APPROVED,
                        'is_verified' => USER_EMAIL_VERIFIED,
                        'payment_mode' => COD,
                        'language_id' => 1,
                        'registration_steps' => 1,
                        'token' => Helper::generate_token(),
                        'token_expiry' => Helper::generate_token_expiry(),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ],
                ]);

            }

        }

        /**
         * @uses Used to add demo provider details
         *
         * @created Vidhya 
         *
         * @updated Vidhya
         */


        if(Schema::hasTable('providers')) {

            $check_user_details = DB::table('providers')->where('email' , 'provider@rentcubo.com')->count();

            if(!$check_user_details) {

                $user_details = DB::table('providers')->insert([
                    [
                        'unique_id' => uniqid(),
                        'username' => 'providerdemo',
                        'name' => 'Provider',
                        'first_name' => 'Provider',
                        'last_name' => 'Provider',
                        'email' => 'provider@rentcubo.com',
                        'password' => \Hash::make('123456'),
                        'picture' =>"https://admin-rentroom.rentcubo.info/placeholder.jpg",
                        'login_by' =>"manual",
                        'device_type' => "web",
                        'status' => PROVIDER_APPROVED,
                        'is_verified' => PROVIDER_EMAIL_VERIFIED,
                        'provider_type'=>0,
                        'payment_mode' => COD,
                        'language_id' => 1,
                        'registration_steps' => 1,
                        'token' => Helper::generate_token(),
                        'token_expiry' => Helper::generate_token_expiry(),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ],
                ]);

            }

            $check_test_details = DB::table('providers')->where('email' , 'test@rentcubo.com')->count();

            if(!$check_test_details) {

                $test_details = DB::table('providers')->insert([
                    [
                        'unique_id' => uniqid(),
                        'username' => 'Test',
                        'name' => 'Test',
                        'first_name' => 'Provider',
                        'last_name' => 'Provider',
                        'email' => 'test@rentcubo.com',
                        'password' => \Hash::make('123456'),
                        'picture' =>"https://admin-rentroom.rentcubo.info/placeholder.jpg",
                        'login_by' =>"manual",
                        'device_type' => "web",
                        'status' => PROVIDER_APPROVED,
                        'is_verified' => PROVIDER_EMAIL_VERIFIED,
                        'payment_mode' => COD,
                        'language_id' => 1,
                        'registration_steps' => 1,
                        'token' => Helper::generate_token(),
                        'token_expiry' => Helper::generate_token_expiry(),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ],
                ]);

            }

        }

    }
}
