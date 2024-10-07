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
        Schema::table('marketing_area_districts', function (Blueprint $table) {
            $table->uuid("marketing_area_district_group_id")->after("sub_region_id")->nullable();
            $table->foreign('marketing_area_district_group_id', 'district_group_id_foreign')
                ->references("id")
                ->on("marketing_area_district_groups")
                ->onDelete("cascade");
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
            $table->dropForeign("district_group_id_foreign");
            $table->dropColumn('marketing_area_district_group_id');
        });
    }
};
