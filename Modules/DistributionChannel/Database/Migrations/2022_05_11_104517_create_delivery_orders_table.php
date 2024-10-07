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
        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->uuid('id')->unique();
            $table->primary('id');
            $table->date("date_delivered")->nullable();
            $table->uuid("id_list_dispatch_order");
            $table->foreign("id_list_dispatch_order")
                    ->references("id")
                    ->on("list_dispatch_orders")
                    ->onDelete("cascade");
            $table->string('document')->nullable();
            $table->string('caption')->nullable();
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
        Schema::dropIfExists('delivery_orders');
    }
};
