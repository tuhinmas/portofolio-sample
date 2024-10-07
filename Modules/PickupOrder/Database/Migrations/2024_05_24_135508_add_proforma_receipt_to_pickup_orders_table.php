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
        Schema::table('pickup_orders', function (Blueprint $table) {
            $table->unsignedBigInteger("receipt_id")->after("note")->default("7");
            $table->foreign("receipt_id")
                ->foreign("receipt_id")
                ->references("id")
                ->on("proforma_receipts");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pickup_orders', function (Blueprint $table) {
            $table->dropForeign(["receipt_id"]);
            $table->dropColumn('receipt_id');
        });
    }
};
