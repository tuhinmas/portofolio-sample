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
        Schema::table('pickup_orders', function (Blueprint $table) {
            $table->string("driver_name")->nullable();
            $table->string("driver_phone_number")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pickup_orders', function (Blueprint $table) {
            $table->dropColumn("driver_name");
            $table->dropColumn("driver_phone_number");
        });
    }
};
