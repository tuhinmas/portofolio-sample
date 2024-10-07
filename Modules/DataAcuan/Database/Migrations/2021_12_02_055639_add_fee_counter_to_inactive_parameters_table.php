<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFeeCounterToInactiveParametersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('inactive_parameters', function (Blueprint $table) {
            $table->after("parameter", function($table){
                $table->double("counter_fee", 5, 2)->nullable();
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
        Schema::table('inactive_parameters', function (Blueprint $table) {
            $table->dropColumn('counter_fee');
        });
    }
}
