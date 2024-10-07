<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('address_with_detail_temps', function (Blueprint $table) {
            $table->after("district_id", function ($table) {
                $table->uuid("area_id")->nullable();
                $table->uuid("sub_region_id")->nullable();
                $table->uuid("region_id")->nullable();

                $table->foreign("area_id")
                    ->references("id")
                    ->on("marketing_area_districts")
                    ->onUpdate("cascade");

                $table->foreign("sub_region_id")
                    ->references("id")
                    ->on("marketing_area_sub_regions")
                    ->onUpdate("cascade");

                $table->foreign("region_id")
                    ->references("id")
                    ->on("marketing_area_regions")
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
        Schema::table('address_with_detail_temps', function (Blueprint $table) {
            $table->dropForeign(["area_id"]);
            $table->dropForeign(["sub_region_id"]);
            $table->dropForeign(["region_id"]);

            $table->dropForeign("area_id");
            $table->dropForeign("sub_region_id");
            $table->dropForeign("region_id");
        });
    }
};
