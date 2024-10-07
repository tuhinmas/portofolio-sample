<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInvoiceIdToSalesOrderDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sales_order_details', function (Blueprint $table) {
            $table->after("marketing_fee", function ($table) {
                $table->uuid("proforma_id")->nullable();
                $table->foreign("proforma_id")
                    ->references("id")
                    ->on("invoices")
                    ->onDelete("cascade");
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
        Schema::table('sales_order_details', function (Blueprint $table) {
            $table->dropForeign(['proforma_id']);
            $table->dropColumn('proforma_id');
        });
    }
}
