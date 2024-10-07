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
        Schema::create('pickup_order_histories', function (Blueprint $table) {
            $table->uuid("id")->unique()->primary();
            $table->uuid("pickup_order_id");

            $table->foreign('pickup_order_id', 'fk_pc_orders')
                ->references('id')
                ->on('pickup_orders')
                ->onDelete('cascade');

            $table->integer('status_before')->nullable();
            $table->integer('status_after')->nullable();
            
            $table->uuid("change_by")->nullable();
            $table->foreign("change_by")
                ->references("id")
                ->on("personels")
                ->onDelete("cascade");    
            $table->text('note')->nullable();
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
        Schema::dropIfExists('pickup_order_histories');
    }
};
