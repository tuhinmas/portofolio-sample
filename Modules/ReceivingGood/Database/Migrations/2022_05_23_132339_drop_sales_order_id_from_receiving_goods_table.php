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
        Schema::table('receiving_goods', function (Blueprint $table) {
            $table->dropColumn('sales_order_id');
            $table->dropColumn('shipping_number');
            $table->dropColumn('shipping_id');
            $table->dropColumn('shipping_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('receiving_goods', function (Blueprint $table) {
            $table->uuid('sales_order_id');
            $table->uuid('shipping_number');
            $table->uuid('shipping_id');
            $table->string('shipping_status');
        });
    }
};
