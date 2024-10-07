<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeStatusColorsFieldInStoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stores', function (Blueprint $table) {
            if(Schema::hasColumn('stores', 'status_color')){
                $table->dropColumn('status_color');
            }            
        });
        Schema::table('stores', function (Blueprint $table) {
            $table->after('status', function($table){
                $table->enum('status_color',['c2c2c2','f78800','ff0000','000000'])->default('c2c2c2');
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
            $table->dropColumn('status_color');
        });
    }
}
