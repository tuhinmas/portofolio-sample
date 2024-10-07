<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsCountInLogPointMarketingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('log_worker_point_marketing', function (Blueprint $table) {
            $table->integer('is_count')->nullable()->after("sales_order_id");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('log_worker_point_marketing', function (Blueprint $table) {
            $table->dropColumn('is_count');
        });
    }
}
