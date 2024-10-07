<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateForeignSalesOrderOnInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(["sales_order_id"]);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreign("sales_order_id")
                ->references("id")
                ->on("sales_orders")
                ->onDelete("cascade")
                ->onUpdate("cascade");
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
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropForeign(["sales_order_id"]);
            });

            
            Schema::table('invoices', function (Blueprint $table) {
                $table->foreign("sales_order_id")
                    ->references("id")
                    ->on("sales_orders")
                    ->onDelete("cascade")
                    ->onUpdate("cascade");
            });
        });
    }
}
