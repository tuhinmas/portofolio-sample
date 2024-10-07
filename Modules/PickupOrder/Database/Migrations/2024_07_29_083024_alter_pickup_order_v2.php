<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pickup_orders', function (Blueprint $table) {
            DB::statement("ALTER TABLE pickup_orders CHANGE `status` `status`ENUM('planned','loaded','failed','delivered','canceled','received','revised','checked') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        });

        Schema::table('pickup_order_details', function (Blueprint $table) {
            $table->integer("quantity_actual_checked")->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pickup_order_details', function (Blueprint $table) {
            $table->dropColumn("quantity_actual_checked");
        });
    }
};
