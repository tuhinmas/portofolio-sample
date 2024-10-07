<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class NominalToDecimalOnEntrusmentPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('entrusment_payments', function (Blueprint $table) {
            $table->decimal("nominal", 15, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('entrusment_payments', function (Blueprint $table) {
            $table->decimal("nominal", 15, 2)->change();
        });
    }
}
