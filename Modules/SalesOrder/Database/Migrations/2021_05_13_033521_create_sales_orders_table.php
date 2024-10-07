<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSalesOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->uuid('id')->unique();
            $table->primary('id');
            $table->uuid('store_id');
            $table->uuid('user_id')->nullable();
            $table->uuid('payment_method_id')->nullable();
            $table->string('recipient_phone_number')->nullable();
            $table->longText('delivery_location')->nullable();
            $table->integer('sub_total')->nullable();
            $table->integer('discount')->nullable();
            $table->integer('total')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
            $table->foreign('payment_method_id')
                  ->references('id')
                  ->on('payment_methods')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sales_orders');
    }
}
