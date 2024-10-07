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
        Schema::table('log_sales_order_origins', function (Blueprint $table) {
            $table->tinyInteger("is_direct_price_set")->default(0)->after("type");
            $table->tinyInteger("is_direct_set")->default(0)->after("type");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('log_sales_order_origins', function (Blueprint $table) {
            $table->dropColumn('is_direct_set');
            $table->dropColumn('is_direct_price_set');
        });
    }
};
