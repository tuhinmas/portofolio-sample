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
            $table->integer("quantity_unit")->after("package_weight")->nullable();
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
            $table->dropColumn('quantity_unit');
        });
    }
};
