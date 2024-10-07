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
        Schema::create('receiving_good_indirect_sales', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->primary("id");
            $table->uuid("sales_order_id");
            $table->string("delivery_number");
            $table->string("status");
            $table->longText('note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign("sales_order_id")
                  ->references("id")
                  ->on("sales_orders")
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
        Schema::dropIfExists('receiving_good_indirect_sales');
    }
};
