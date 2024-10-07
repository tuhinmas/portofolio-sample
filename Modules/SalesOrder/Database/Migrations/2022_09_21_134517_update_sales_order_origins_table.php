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
            $table->dropColumn('confirmed_at');
        });

        Schema::table('sales_order_origins', function (Blueprint $table) {
            $table->timestamp("confirmed_at")->nullable()->after("is_fee_counted");
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
            $table->dropColumn('confirmed_at');
        });
        
        Schema::table('sales_order_origins', function (Blueprint $table) {
            $table->timestamp("confirmed_at")->nullable()->after("is_fee_counted");
        });
    }
};
