<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddV1HostRelatedMigrations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (!Schema::hasTable('service_locations')) {

            Schema::create('service_locations', function (Blueprint $table) {
                $table->increments('id');
                $table->string('unique_id');
                $table->string('name');
                $table->string('picture');
                $table->text('description');
                $table->string('address');
                $table->string('cover_radius')->default(10);
                $table->double('latitude',15,8)->default(0.000000);
                $table->double('longitude',15,8)->default(0.000000);
                $table->integer('status')->default(1);
                $table->timestamps();
            });

        }

        if (!Schema::hasTable('hosts')) {

            Schema::create('hosts', function (Blueprint $table) {
                $table->increments('id');
                $table->string('unique_id');
                $table->integer('provider_id');
                $table->string('host_name');
                $table->string('host_type');
                $table->text('description');
                $table->string('picture')->default(asset('host-placeholder.jpg'));
                $table->integer('service_location_id');
                $table->float('total_spaces')->default(1);
                $table->text('access_note')->nullable();
                $table->string('access_method')->default("");
                $table->string('security_code')->default("");
                $table->string('host_owner_type')->default("");
                $table->float('per_hour', 8,2)->default(0.00);
                $table->string('width_of_space')->default("");
                $table->string('height_of_space')->default("");
                $table->string('amenities')->default("");
                $table->string('available_days')->default('1,2,3,4,5,6,7');
                $table->double('latitude',15,8);
                $table->double('longitude',15,8);
                $table->text('full_address')->nullable();
                $table->string('street_details')->nullable();
                $table->string('city')->nullable();
                $table->string('state')->nullable();
                $table->string('country')->nullable();
                $table->string('zipcode')->nullable();
                $table->string('checkin')->nullable();
                $table->string('checkout')->nullable();
                $table->string('min_days')->default(0);
                $table->string('max_days')->default(0);
                $table->float('base_price')->default(0.00);
                $table->float('per_guest_price')->default(0.00);
                $table->float('per_day')->default(0.00);
                $table->float('per_week')->default(0.00);
                $table->float('per_month')->default(0.00);
                $table->float('cleaning_fee')->default(0.00);
                $table->float('tax_price')->default(0.00);
                $table->float('overall_ratings')->default(0);
                $table->integer('total_ratings')->default(0);
                $table->tinyInteger('is_admin_verified')->default(0);
                $table->tinyInteger('admin_status')->default(0);
                $table->tinyInteger('status')->default(0);
                $table->string('uploaded_by')->default(PROVIDER);
                $table->timestamps();
            
            });

        }

        if (!Schema::hasTable('host_details')) {

            Schema::create('host_details', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('host_id');
                $table->integer('provider_id');
                $table->tinyInteger('status')->default(0);
                $table->timestamps();
            
            });
        }

        if (!Schema::hasTable('host_galleries')) {

            Schema::create('host_galleries', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('host_id');
                $table->string('picture');
                $table->string('caption')->default("");
                $table->tinyInteger('is_default')->default(0);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });

        }

        if (!Schema::hasTable('host_inventories')) {

            Schema::create('host_inventories', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('host_id');
                // $table->integer('common_question_id');
                $table->tinyInteger('status')->default(0);
                $table->timestamps();
            });
            
        }

        if (!Schema::hasTable('host_availability_lists')) {

            Schema::create('host_availability_lists', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('host_id');
                $table->integer('provider_id');
                $table->dateTime('from_date');
                $table->dateTime('to_date');
                $table->string('spaces')->default(0);
                $table->tinyInteger('type')->default(1);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('host_availabilities')) {

            Schema::create('host_availabilities', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('host_id');
                $table->integer('provider_id');
                $table->tinyInteger('checkin_status')->default(0);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
            
        }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('service_locations');
        Schema::dropIfExists('hosts');
        Schema::dropIfExists('host_details');
        Schema::dropIfExists('host_galleries');
        Schema::dropIfExists('host_inventories');
        Schema::dropIfExists('host_availability_lists');
        Schema::dropIfExists('host_availabilities');
    }
}
