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
        Schema::table('adjustment_stock', function (Blueprint $table) {
            $table->string("is_first_stock")->default("1")->after("product_undelivered_by_distributor");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('adjustment_stock', function (Blueprint $table) {
            $table->dropColumn('is_first_stock');
        });
    }
};
