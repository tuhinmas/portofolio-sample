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
        Schema::table('dispatch_order_detail', function (Blueprint $table) {
            DB::statement("ALTER TABLE dispatch_order_detail MODIFY COLUMN package_weight decimal(20)");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dispatch_order_detail', function (Blueprint $table) {
            $table->dropColumn('package_weight');
        });
    }
};
