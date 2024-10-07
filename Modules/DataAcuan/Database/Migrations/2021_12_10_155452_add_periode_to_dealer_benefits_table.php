<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPeriodeToDealerBenefitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dealer_benefits', function (Blueprint $table) {
            $table->after("additional", function($table){
                $table->date("start_period")->nullable();
                $table->date("end_period")->nullable();
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
        Schema::table('dealer_benefits', function (Blueprint $table) {
            $table->dropColumn('start_period');
            $table->dropColumn('end_period');
        });
    }
}
