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
        Schema::create('delivery_order_receipts', function (Blueprint $table) {
            $table->uuid('id')->unique();
            $table->primary('id');
            $table->uuid("id_delivery_orders")->nullable();
            $table->foreign('id_delivery_orders')
                    ->references('id')
                    ->on('delivery_orders');
            $table->string('siup')->nullable();
            $table->string('npwp')->nullable();
            $table->string('tdp')->nullable();
            $table->string('ho')->nullable();
            $table->string('note')->nullable();
            $table->string('company_name')->nullable();
            $table->string('company_address')->nullable();
            $table->string('company_telephone')->nullable();
            $table->string('company_hp')->nullable();
            $table->string('company_email')->nullable();
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
        Schema::dropIfExists('delivery_order_receipts');
    }
};
