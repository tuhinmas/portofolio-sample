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
        Schema::table('log_marketing_fee_target_active_counters', function (Blueprint $table) {
            $table->string("type")->after("sales_order_id");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('log_marketing_fee_target_active_counters', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
