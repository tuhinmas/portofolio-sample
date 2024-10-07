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
        Schema::table('sales_order_origins', function (Blueprint $table) {
            $table->tinyInteger("is_fee_counted")->after("is_returned")->default(1);
            $table->tinyInteger("is_point_counted")->after("is_returned")->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sales_order_origins', function (Blueprint $table) {
            $table->dropColumn('is_fee_counted');
            $table->dropColumn('is_point_counted');
        });
    }
};
