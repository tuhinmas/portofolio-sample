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
        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->uuid("confirmed_by")->after("image_footer_link")->nullable();
            $table->unsignedBigInteger("receipt_id")->after("image_footer_link")->nullable();

            $table->foreign("confirmed_by")
                ->references("id")
                ->on("personels")
                ->onDelete("cascade");

            $table->foreign("receipt_id")
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
        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->dropForeign(['receipt_id']);
            $table->dropColumn('receipt_id');
            
            $table->dropForeign(['confirmed_by']);
            $table->dropColumn('confirmed_by');
        });
    }
};
