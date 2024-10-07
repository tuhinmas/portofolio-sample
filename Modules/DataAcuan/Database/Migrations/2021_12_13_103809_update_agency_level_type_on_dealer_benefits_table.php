<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateAgencyLevelTypeOnDealerBenefitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dealer_benefits', function (Blueprint $table) {
            $table->dropColumn('agency_level_id');
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
            $table->after("payment_method_id", function($table){
                $table->uuid("agency_level_id");
            });
        });
    }
}
