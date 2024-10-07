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
        Schema::table('invoice_proformas', function (Blueprint $table) {
            $table->uuid("confirmed_by")->after("link")->nullable();
            $table->foreign("confirmed_by")
                ->references("id")
                ->on("personels")
                ->onDelete("cascade");
        });
       
        Schema::table('invoice_proformas', function (Blueprint $table) {
            $table->unsignedBigInteger("receipt_id")->after("confirmed_by")->nullable();
            $table->foreign("receipt_id")
                ->references("id")
                ->on("proforma_receipts")
                ->onDelete("cascade");
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
            $table->dropForeign(['confirmed_by']);
            $table->dropColumn('confirmed_by');
            $table->dropForeign(['receipt_id']);
            $table->dropColumn('receipt_id');
        });
    }
};
