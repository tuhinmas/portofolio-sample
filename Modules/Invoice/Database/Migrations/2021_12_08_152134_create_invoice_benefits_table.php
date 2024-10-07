<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvoiceBenefitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoice_benefits', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->primary("id");
            $table->uuid("invoice_id");
            $table->uuid("benefit_id");
            $table->uuid("product_id");
            $table->enum("type", ["1", "2"])->comment("1 => diskon, 2 => rebate");
            $table->double("nominal", 15, 2);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign("invoice_id")
                  ->references("id")
                  ->on("invoices")
                  ->onDelete("cascade");

            $table->foreign("benefit_id")
                  ->references("id")
                  ->on("dealer_benefits")
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
        Schema::dropIfExists('invoice_benefits');
    }
}
