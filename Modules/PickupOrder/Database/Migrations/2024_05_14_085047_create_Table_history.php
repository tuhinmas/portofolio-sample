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
        Schema::create('pickup_load_histories', function (Blueprint $table) {
            $table->uuid("id")->unique()->primary();
            $table->uuid("pickup_order_id");

            $table->foreign('pickup_order_id')
                ->references('id')
                ->on('pickup_orders')
                ->onDelete('cascade');

            $table->json('dispatch')->nullable();
            $table->enum('status',["created","canceled"])->nullable();

            $table->uuid("created_by");
            $table->foreign('created_by')
                ->references('id')
                ->on('personels')
                ->onDelete('cascade');

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
        Schema::dropIfExists('pickup_load_histories');
    }
};
