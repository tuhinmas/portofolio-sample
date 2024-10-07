<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateMdmOnMarketingAreaRegionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('marketing_area_regions', function (Blueprint $table) {
            $table->renameColumn("MDM", "personel_id");
        });

        Schema::table('marketing_area_sub_regions', function (Blueprint $table) {
            $table->renameColumn("RMC", "personel_id");
        });

        Schema::table('marketing_area_cities', function (Blueprint $table) {
            $table->renameColumn("RM", "personel_id");
        });
        
        Schema::table('marketing_area_districts', function (Blueprint $table) {
            $table->renameColumn("rm", "personel_id");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('', function (Blueprint $table) {

        });
    }
}
