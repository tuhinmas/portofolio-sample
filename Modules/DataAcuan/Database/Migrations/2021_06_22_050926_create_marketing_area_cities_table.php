<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMarketingAreaCitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('marketing_area_cities', function (Blueprint $table) {
            $table->uuid('id')->unique();
            $table->primary('id');
            $table->string('name');
            $table->uuid('sub_region_id');
            $table->uuid('RM')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('sub_region_id')
                  ->references('id')
                  ->on('marketing_area_sub_regions')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('marketing_area_cities');
    }
}
