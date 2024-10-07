<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangColumnTypeOnStoreTempsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('store_temps', function (Blueprint $table) {
           $table->text("gmaps_link")->change(); 
        });
        
        Schema::table('stores', function (Blueprint $table) {
           $table->text("gmaps_link")->change(); 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('store_temps', function (Blueprint $table) {
            
        });
    }
}
