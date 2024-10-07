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
        Schema::create('pickup_order_dispatches', function (Blueprint $table) {
            $table->uuid("id")->unique()->primary();
            $table->uuid("pickup_order_id");

            $table->foreign('pickup_order_id')
                ->references('id')
                ->on('pickup_orders')
                ->onDelete('cascade');

            $table->uuid('dispatch_id')->nullable();
            $table->enum('dispatch_type', ["dispatch_order","dispatch_promotion"])->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop("pickup_order_dispatches");
    }
};
