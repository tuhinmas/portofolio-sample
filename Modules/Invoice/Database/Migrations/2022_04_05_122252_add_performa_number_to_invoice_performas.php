<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoice_proformas', function (Blueprint $table) {
            $table->after("invoice_proforma_number", function ($table) {
                $table->bigInteger("proforma_number")->nullable();
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
        Schema::table('invoice_proformas', function (Blueprint $table) {
            $table->dropColumn('proforma_number');
        });
    }
};
