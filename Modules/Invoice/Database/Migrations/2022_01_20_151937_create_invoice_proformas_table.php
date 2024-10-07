<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoiceProformasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoice_proformas', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->primary("id");
            $table->uuid("personel_id")->comment("personel who make invoice");
            $table->uuid("invoice_id")->comment("proforma if status settle");
            $table->string("invoice_proforma_number");
            $table->text("link");
            $table->timestamps();
            $table->softDeletes();
            $table->foreign("personel_id")
                ->references("id")
                ->on("personels")
                ->onDelete("cascade");

            $table->foreign("invoice_id")
                ->references("id")
                ->on("invoices")
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
        Schema::dropIfExists('invoice_proformas');
    }
}
