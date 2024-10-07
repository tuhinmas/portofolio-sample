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
        Schema::table('sales_order_status_fee_shoulds', function (Blueprint $table) {
            $table->timestamp("confirmed_at")->after("status_fee_id")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sales_order_status_fee_shoulds', function (Blueprint $table) {
            $table->dropColumn('confirmed_at');
        });
    }
};
