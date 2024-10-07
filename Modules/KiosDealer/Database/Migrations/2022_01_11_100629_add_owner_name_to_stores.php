<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOwnerNameToStores extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->after("name", function($table){
                $table->string("owner_name")->nullable();
            });
        });
        
        Schema::table('stores', function (Blueprint $table) {
            $table->after("telephone", function($table){
                $table->string("second_telephone")->nullable();
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('owner_name');
            $table->dropColumn('second_telephone');
        });
    }
}
