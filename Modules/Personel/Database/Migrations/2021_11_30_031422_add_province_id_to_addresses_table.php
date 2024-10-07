<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddProvinceIdToAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->after("detail_address", function($table){
                $table->string("province_id")->nullable();
                $table->string("city_id")->nullable();
                $table->string("district_id")->nullable();
                $table->string("post_code")->nullable();
                $table->foreign("province_id")
                      ->references("id")
                      ->on("indonesia_provinces");
                $table->foreign("city_id")
                      ->references("id")
                      ->on("indonesia_cities");
                $table->foreign("district_id")
                      ->references("id")
                      ->on("indonesia_districts");
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
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropForeign(['province_id']);
            $table->dropForeign(['city_id']);
            $table->dropForeign(['district_id']);
            $table->dropColumn('province_id');
            $table->dropColumn('city_id');
            $table->dropColumn('district_id');
            $table->dropColumn('post_code');
        });
    }
}
