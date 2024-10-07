<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenamaAdditionalOnDealerBenefitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dealer_benefits', function (Blueprint $table) {
            $table->renameColumn("additional", "benefit_discount");
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
            $table->renameColumn("benefit_discount","additional");
        });
    }
}
