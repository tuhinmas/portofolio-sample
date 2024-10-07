<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusRevisedToSalesOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            DB::statement("ALTER TABLE sales_orders CHANGE `status` `status`ENUM(
                'draft',
                'submited',
                'confirmed', 
                'canceled', 
                'proofed', 
                'rejected', 
                'pending', 
                'revised',
                'excluded'
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft'");
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
            DB::statement("ALTER TABLE sales_orders CHANGE `status` `status`ENUM(
                'draft',
                'submited',
                'confirmed', 
                'canceled', 
                'proofed', 
                'rejected', 
                'pending',
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft'");
        });
    }
}
