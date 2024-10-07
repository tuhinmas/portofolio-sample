<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProvinceRegionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('province_regions', function (Blueprint $table) {
            $table->id();
            $table->uuid("region_id");
            $table->char("province_id");
            $table->timestamps();
            $table->softDeletes();
            $table->foreign("region_id")
                  ->references("id")
                  ->on("marketing_area_regions")
                  ->onUpdate("cascade");
            $table->foreign("province_id")
                  ->references("id")
                  ->on("indonesia_provinces")
                  ->onUpdate("cascade");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('province_regions');
    }
}
