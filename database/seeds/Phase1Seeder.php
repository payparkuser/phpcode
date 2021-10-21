<?php

use Illuminate\Database\Seeder;

class Phase1Seeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('settings')->insert([
    		[
		        'key' => 'identity_verification_preview',
		        'value' => env('APP_URL')."/verification-placeholder.jpg"
		    ]
		]);
    }
}
