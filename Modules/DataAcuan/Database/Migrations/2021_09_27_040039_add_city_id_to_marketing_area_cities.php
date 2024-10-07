<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCityIdToMarketingAreaCities extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('marketing_area_cities', function (Blueprint $table) {
            $table->after("name", function($table){
                $table->char("city_id")->nullable();
                $table->foreign("city_id")
                      ->references("id")
                      ->on("indonesia_cities")
                      ->onUpdate("cascade");
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
        Schema::table('marketing_area_cities', function (Blueprint $table) {
            $table->dropForeign(['city_id']);
            $table->dropColumn('city_id');
        });
    }
}
