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
            $table->integer("planned_package_to_send")->nullable();
            $table->float("planned_package_weight")->nullable();
            $table->integer("planned_quantity_unit")->nullable();
        });

        Schema::table('discpatch_order', function (Blueprint $table) {
            $table->text("dispatch_note")->nullable();
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
            $table->dropColumn("planned_package_to_send","planned_package_weight","planned_quantity_unit");
        });

        Schema::table('discpatch_order', function (Blueprint $table) {
            $table->dropColumn("dispatch_note");
        });
    }
};
