<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnSeveralTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->text("manual_change_log")->nullable();
        });
        Schema::table('sales_order_details', function (Blueprint $table) {
            $table->text("manual_change_log")->nullable();
        });
        Schema::table('invoice_proformas', function (Blueprint $table) {
            $table->text("manual_change_log")->nullable();
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->text("manual_change_log")->nullable();
        });
        Schema::table('distributor_contracts', function (Blueprint $table) {
            $table->text("manual_change_log")->nullable();
        });
        Schema::table('personels', function (Blueprint $table) {
            $table->text("manual_change_log")->nullable();
        });
        Schema::table('fore_casts', function (Blueprint $table) {
            $table->text("manual_change_log")->nullable();
        });
        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->text("manual_change_log")->nullable();
        });
        Schema::table('discpatch_order', function (Blueprint $table) {
            $table->text("manual_change_log")->nullable();
        });
        Schema::table('dispatch_order_detail', function (Blueprint $table) {
            $table->text("manual_change_log")->nullable();
        });
        Schema::table('receiving_goods', function (Blueprint $table) {
            $table->text("manual_change_log")->nullable();
        });
        Schema::table('receiving_good_details', function (Blueprint $table) {
            $table->text("manual_change_log")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn("manual_change_log");
        });
        Schema::table('sales_order_details', function (Blueprint $table) {
            $table->dropColumn("manual_change_log");
        });
        Schema::table('invoice_proformas', function (Blueprint $table) {
            $table->dropColumn("manual_change_log");
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn("manual_change_log");
        });
        Schema::table('distributor_contracts', function (Blueprint $table) {
            $table->dropColumn("manual_change_log");
        });
        Schema::table('personels', function (Blueprint $table) {
            $table->dropColumn("manual_change_log");
        });
        Schema::table('fore_casts', function (Blueprint $table) {
            $table->dropColumn("manual_change_log");
        });
        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->dropColumn("manual_change_log");
        });
        Schema::table('discpatch_order', function (Blueprint $table) {
            $table->dropColumn("manual_change_log");
        });
        Schema::table('dispatch_order_detail', function (Blueprint $table) {
            $table->dropColumn("manual_change_log");
        });
        Schema::table('receiving_goods', function (Blueprint $table) {
            $table->dropColumn("manual_change_log");
        });
        Schema::table('receiving_good_details', function (Blueprint $table) {
            $table->dropColumn("manual_change_log");
        });
    }
}
