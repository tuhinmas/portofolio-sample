<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangePaymentStatusOnInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            \DB::statement("ALTER TABLE `invoices` CHANGE `payment_status` `payment_status` enum('paid','unpaid','settle');");
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
            \DB::statement("ALTER TABLE `invoices` CHANGE `payment_status` `payment_status` enum('paid','unpaid','paid off');");
        });
    }
}
