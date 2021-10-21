<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddV11Fields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('documents', function (Blueprint $table) {
            $table->string('type')->default('others');
            $table->string('picture')->default(envfile('APP_URL')."/placeholder.jpg");
        });

        Schema::table('providers', function (Blueprint $table) {
            $table->string('identity_verification_file')->default('');
        });

        Schema::table('hosts', function (Blueprint $table) {
            $table->integer('length_of_space')->default(0)->after('height_of_space');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::table('documents', function (Blueprint $table) {

            $table->dropColumn('type');
            $table->dropColumn('picture');

        });

        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn('identity_verification_file');
        });

        Schema::table('hosts', function (Blueprint $table) {
            $table->dropColumn('length_of_space');
        });
    }
}
