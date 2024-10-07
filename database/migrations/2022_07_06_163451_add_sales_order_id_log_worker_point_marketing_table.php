<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSalesOrderIdLogWorkerPointMarketingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('log_worker_point_marketing', function (Blueprint $table) {
            $table->string('sales_order_id')->nullable()->after("point_marketing_id");
            
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
            $table->dropColumn('sales_order_detail_id');
            
        });
    }
}
