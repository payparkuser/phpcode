<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddV20Fields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hosts', function (Blueprint $table) {
            $table->integer('is_automatic_booking')->default(NO)->after('status');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->integer('is_automatic_booking')->after('status')->default(NO);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hosts', function (Blueprint $table) {
            $table->dropColumn('is_automatic_booking');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('is_automatic_booking');
        });
    }
}
