<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPrefixToDealersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dealers', function (Blueprint $table) {
            $table->after("dealer_id", function($table){
                $table->string("prefix")->nullable();
            });
        });
        
        Schema::table('dealers', function (Blueprint $table) {
            $table->after("name", function($table){
                $table->string("sufix")->nullable();
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
        Schema::table('dealers', function (Blueprint $table) {
            $table->dropColumn('prefix');
            $table->dropColumn('sufix');
        });
    }
}
