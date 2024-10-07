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
        Schema::table('pickup_order_details', function (Blueprint $table) {
            $table->enum('pickup_type', ["load","unload"])->after("pickup_order_id")->default("load");
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
            $table->dropColumn('pickup_type');
        });
    }
};
