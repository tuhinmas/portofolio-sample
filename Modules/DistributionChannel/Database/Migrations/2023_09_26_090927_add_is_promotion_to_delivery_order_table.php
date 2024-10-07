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
        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->after("order_number", function ($table) {
                $table->string("is_promotion")->default("0");
                $table->integer("order_number_promotion")->nullable();
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
        Schema::table('delivery_order', function (Blueprint $table) {
            $table->dropColumn('is_promotion');
            $table->dropColumn("order_number_promotion");
        });
    }
};
