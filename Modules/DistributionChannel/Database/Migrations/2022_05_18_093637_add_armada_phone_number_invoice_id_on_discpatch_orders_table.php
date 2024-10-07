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
        Schema::table('discpatch_order', function (Blueprint $table) {
            $table->after("driver_name", function ($table) {
                $table->uuid("invoice_id")->nullable();
                $table->foreign('invoice_id')
                    ->references('id')
                    ->on('invoices')
                    ->onDelete('cascade');
                $table->string("armada_phone_number")->nullable();
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
        Schema::table('discpatch_order', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->string("armada_phone_number")->nullable();
        });
    }
};
