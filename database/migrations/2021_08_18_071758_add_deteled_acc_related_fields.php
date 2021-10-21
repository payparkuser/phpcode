<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeteledAccRelatedFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
        Schema::table('providers', function (Blueprint $table) {
            $table->tinyInteger('is_deleted')->default(NO)->after('is_document_verified');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->tinyInteger('is_deleted')->default(NO)->after('verification_code_expiry');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        

        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn('is_deleted'); 
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_deleted'); 
        });
    }
}
