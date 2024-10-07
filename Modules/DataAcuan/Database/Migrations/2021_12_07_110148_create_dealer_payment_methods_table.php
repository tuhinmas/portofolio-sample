<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDealerPaymentMethodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dealer_payment_methods', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->primary("id");
            $table->uuid("dealer_id");
            $table->uuid("payment_method_id");
            $table->timestamps();
            $table->softDeletes();
            $table->foreign("dealer_id")
                  ->references("id")
                  ->on("dealers")
                  ->onDelete("cascade");
            $table->foreign("payment_method_id")
                  ->references("id")
                  ->on("payment_methods")
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
        Schema::dropIfExists('dealer_payment_methods');
    }
}
