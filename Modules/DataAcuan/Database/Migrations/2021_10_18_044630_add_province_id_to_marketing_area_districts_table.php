<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProvinceIdToMarketingAreaDistrictsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('marketing_area_districts', function (Blueprint $table) {
            if (!Schema::hasColumn('marketing_area_districts', 'province_id')) {
                $table->after("id", function ($table) {
                    $table->uuid("province_id")->nullable();
                    $table->foreign("province_id")
                        ->references("id")
                        ->on("indonesia_provinces")
                        ->onDelete("cascade")
                        ->onUpdate("cascade");
                });
            }
        });

        Schema::table('marketing_area_districts', function (Blueprint $table) {
            if (Schema::hasColumn('marketing_area_districts', 'marketing_area_city_id')) {
                $table->dropForeign("marketing_area_districts_city_id_foreign");
            }
            if (Schema::hasColumn('marketing_area_districts', 'marketing_area_city_id')) {
                $table->dropColumn("marketing_area_city_id");
            }
        });

        Schema::table('marketing_area_districts', function (Blueprint $table) {
            if (!Schema::hasColumn('marketing_area_districts', 'sub_region_id')) {
                $table->after("personel_id", function ($table) {
                    $table->uuid("sub_region_id")->nullable();
                    $table->foreign("sub_region_id")
                        ->references("id")
                        ->on("marketing_area_sub_regions")
                        ->onDelete("cascade");
                });
            }
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
            if (Schema::hasColumn("marketing_area_districts", "province_id")) {
                $table->dropForeign(['province_id']);
                $table->dropColumn('province_id');
            }

            if (Schema::hasColumn("marketing_area_districts", "province_id")) {
                $table->dropForeign('marketing_area_districts_sub_region_id_foreign');
                $table->dropColumn('sub_region_id ');
            }
        });

        Schema::table('marketing_area_districts', function (Blueprint $table) {
            $table->after("personel_id", function ($table) {
                $table->uuid("marketing_area_city_id")->nullable();
                $table->foreign("marketing_area_city_id")
                    ->references("id")
                    ->on("marketing_area_cities")
                    ->onDelete("cascade");
            });
        });
    }
}
