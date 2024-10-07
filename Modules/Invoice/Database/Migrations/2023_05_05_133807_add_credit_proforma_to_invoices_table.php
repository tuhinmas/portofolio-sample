<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->after("receipt_id", function ($table) {
                $table->double("credit_proforma", 15, 2)->default(0);
                $table->string("credit_note")->nullable();
                $table->boolean("change_locked")->default(0);
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
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('credit_proforma');
            $table->dropColumn('credit_note');
            $table->dropColumn('change_locked');
        });
    }
};
