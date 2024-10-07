<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPersonelForeignRegionToMarketingAreaRegionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('marketing_area_regions', function (Blueprint $table) {
            $table->foreign("personel_id")
                  ->references("id")
                  ->on("personels")
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
        Schema::table('marketing_area_regions', function (Blueprint $table) {
            $table->dropForeign(['personel_id']);
        });
    }
}
