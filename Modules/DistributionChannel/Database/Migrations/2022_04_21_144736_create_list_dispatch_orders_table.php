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
        Schema::create('list_dispatch_orders', function (Blueprint $table) {
            $table->uuid('id')->unique();
            $table->primary('id');
            $table->uuid("id_invoice")->nullable();
            $table->foreign("id_invoice")
                ->references("id")
                ->on("invoices");
            $table->string("proforma_number");
            $table->integer("order_number")->nullable();
            $table->string("dispatch_order_number");
            $table->date("dispatch_date");
            $table->text("destination");
            $table->integer("quantity");
            $table->integer("weight");
            $table->enum("status", ["planned","issued","canceled"]);
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
        Schema::dropIfExists('list_dispatch_orders');
    }
};
