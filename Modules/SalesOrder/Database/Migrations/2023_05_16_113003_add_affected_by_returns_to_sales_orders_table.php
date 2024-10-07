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
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->uuid("afftected_by_return")->after("change_locked")->nullable();
            $table->foreign("afftected_by_return")
                ->references("id")
                ->on("sales_orders");
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
            $table->dropForeign(['afftected_by_return']);
            $table->dropColumn('afftected_by_return');
        });
    }
};
