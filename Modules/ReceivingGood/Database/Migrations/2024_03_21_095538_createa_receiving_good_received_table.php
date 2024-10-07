<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('receiving_good_receiveds', function (Blueprint $table) {
            $table->id();
            $table->uuid("delivery_order_id")->unique();
            $table->uuid("receiving_good_id")->unique();
            $table->timestamps();
            $table->foreign("delivery_order_id")
                ->references("id")
                ->on("delivery_orders")
                ->onDelete("cascade");

            $table->foreign("receiving_good_id")
                ->references("id")
                ->on("receiving_goods")
                ->onDelete("cascade");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop("receiving_good_receiveds");
    }
};
