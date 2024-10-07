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
        Schema::create('discpatch_order', function (Blueprint $table) {
            $table->uuid('id')->unique();
            $table->primary('id');
            $table->uuid("id_invoice");
            $table->foreign("id_invoice")
                    ->references("id")
                    ->on("invoices")
                    ->onDelete("cascade");

            $table->uuid("id_armada");
            $table->foreign("id_armada")
                    ->references("id")
                    ->on("drivers")
                    ->onDelete("cascade");

            $table->uuid("id_product");

            $table->foreign("id_product")
                    ->references("id")
                    ->on("products")
                    ->onDelete("cascade");

            $table->uuid("id_list_dispatch_order");
            $table->foreign("id_list_dispatch_order")
                    ->references("id")
                    ->on("list_dispatch_orders")
                    ->onDelete("cascade");
                    
            $table->string("quantity_packet_to_send")->nullable();
            $table->enum("type_driver", ["internal","external"]);
            $table->string("transportation_type")->nullable();
            $table->string("police_number")->nullable();
            $table->date("date_sent");
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
        Schema::dropIfExists('discpatch_order');
    }
};
