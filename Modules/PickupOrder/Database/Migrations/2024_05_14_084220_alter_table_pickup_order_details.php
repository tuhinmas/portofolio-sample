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
            $table->enum("detail_type", ["dispatch_order", "dispatch_promotion"])->nullable();
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
            $table->dropColumn("detail_type");
        });
    }
};
