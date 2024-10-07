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
        Schema::table('fee_target_sharings', function (Blueprint $table) {
            $table->after("quarter", function ($table) {
                $table->integer("sales_quantity")->default(0);
                $table->double("sales_fee", 20, 2)->default(0);
                $table->integer("target_achieved_quantity")->default(0);
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
        Schema::table('fee_target_sharings', function (Blueprint $table) {
                $table->dropColumn('sales_quantity');
                $table->dropColumn('sales_fee');
                $table->dropColumn('target_achieved_quantity');
        });
    }
};
