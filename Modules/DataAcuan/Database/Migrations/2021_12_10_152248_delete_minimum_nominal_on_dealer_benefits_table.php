<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DeleteMinimumNominalOnDealerBenefitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dealer_benefits', function (Blueprint $table) {
            $table->dropColumn('minimum_nominal');
            $table->dropColumn('discount');
            \DB::statement("ALTER TABLE `dealer_benefits` CHANGE `type` `type` enum('1', '2');");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dealer_benefits', function (Blueprint $table) {
            $table->after("agency_level_id", function($table){
                $table->double("minimum_nominal");
                $table->double("discount");
            });
        });
    }
}
