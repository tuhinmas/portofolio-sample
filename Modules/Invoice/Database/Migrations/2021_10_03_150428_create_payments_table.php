<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->primary("id");
            $table->uuid("invoice_id");
            $table->uuid('payment_method_id')->nullable();
            $table->bigInteger("nominal");
            $table->bigInteger("remaining_payment")->default(0);
            $table->uuid("user_id");
            $table->foreign("invoice_id")
                  ->references("id")
                  ->on("invoices")
                  ->onUpdate("cascade");
            $table->foreign("user_id")
                  ->references("id")
                  ->on("users")
                  ->onUpdate("cascade");
            $table->foreign('payment_method_id')
                  ->references('id')
                  ->on('payment_methods')
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
        Schema::dropIfExists('payments');
    }
}
