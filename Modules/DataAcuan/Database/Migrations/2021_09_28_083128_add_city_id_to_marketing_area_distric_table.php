<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCityIdToMarketingAreaDistricTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('marketing_area_districts', 'city_id')) {
            Schema::table('marketing_area_districts', function (Blueprint $table) {
                $table->renameColumn("city_id", "marketing_area_city_id")->change();
            });
        }
        
        Schema::table('marketing_area_districts', function (Blueprint $table) {
            $table->after("id", function($table){
                $table->char("city_id")->nullable();
            });
            $table->after("name", function($table){
                $table->uuid("rm")->nullable();
                $table->foreign("rm")
                    ->references("id")
                    ->on("personels")
                    ->onUpdate("cascade");
            });
        });

        Schema::table('marketing_area_districts', function (Blueprint $table) {
            $table->renameColumn("name", "district_id")->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('marketing_area_districts', function (Blueprint $table) {
            $table->dropForeign(['rm']);
            $table->dropColumn('rm');
            $table->dropColumn('city_id');
            $table->renameColumn("district_id","name")->change();
        });
    }
}
