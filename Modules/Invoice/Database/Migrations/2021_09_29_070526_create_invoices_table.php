<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $now = 
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->primary("id");
            $table->uuid("sales_order_id");
            $table->uuid("user_id");
            $table->string("invoice");
            $table->bigInteger("sub_total");
            $table->bigInteger("discount");
            $table->bigInteger("total");
            $table->enum("payment_status", ["paid", "unpaid", 'paid off'])->default("unpaid");
            $table->timestamps();
            $table->softDeletes();
            $table->foreign("sales_order_id")
                  ->references("id")
                  ->on("sales_orders")
                  ->onUpdate("cascade");
            $table->foreign("user_id")
                  ->references("id")
                  ->on("users")
                  ->onUpdate("cascade");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invoices');
    }
}
