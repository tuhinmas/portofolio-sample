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
        Schema::create('dispatch_order_detail', function (Blueprint $table) {
            $table->uuid('id')->unique();
            $table->primary('id');
            $table->uuid("id_dispatch_order");
            $table->foreign("id_dispatch_order")
                ->references("id")
                ->on("discpatch_order");
            $table->uuid("id_product");
            $table->foreign("id_product")
                ->references("id")
                ->on("products");
            $table->integer("quantity_packet_to_send")->nullable();
            $table->integer("package_weight")->nullable();
            $table->date("date_received")->nullable();
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
        Schema::dropIfExists('dispatch_order_detail');
    }
};
