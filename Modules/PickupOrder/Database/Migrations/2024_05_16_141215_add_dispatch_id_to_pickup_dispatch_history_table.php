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
        Schema::table('pickup_load_histories', function (Blueprint $table) {
            $table->after("pickup_order_id", function($table){
                $table->uuid("dispatch_id");
                $table->enum('dispatch_type', ["dispatch_order","dispatch_promotion"])->nullable();
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pickup_load_histories', function (Blueprint $table) {
            $table->dropColumn('dispatch_id');
            $table->dropColumn('dispatch_type');
        });
    }
};
