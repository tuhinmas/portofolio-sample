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
        Schema::table('pickup_order_details', function (Blueprint $table) {
            $table->double("estimate_weight", 20, 2)->after('weight')->nullable();
        });

        \DB::update("update pickup_order_details set estimate_weight = weight * quantity_unit_load");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pickup_order_details', function (Blueprint $table) {
            $table->dropColumn("estimate_weight");
        });
    }
};
