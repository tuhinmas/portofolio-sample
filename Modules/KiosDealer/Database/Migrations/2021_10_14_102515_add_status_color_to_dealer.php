<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusColorToDealer extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
            Schema::table('dealers', function (Blueprint $table) {
                if(Schema::hasColumn('dealers', 'status_color')){
                    $table->dropColumn('status_color');
                }            
            });
            Schema::table('dealers', function (Blueprint $table) {
                $table->after('status', function($table){
                    $table->enum('status_color',['c2c2c2','f78800','ff0000','000000', '505050'])->default('505050');
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

        });
    }
}
