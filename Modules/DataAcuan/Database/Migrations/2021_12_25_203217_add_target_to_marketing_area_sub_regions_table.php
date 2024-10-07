<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTargetToMarketingAreaSubRegionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('marketing_area_sub_regions', function (Blueprint $table) {
            $table->after("personel_id", function($table){
                $table->double("target", 15, 2)->nullable();
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
        Schema::table('marketing_area_sub_regions', function (Blueprint $table) {
            $table->dropColumn('target');
        });
    }
}
