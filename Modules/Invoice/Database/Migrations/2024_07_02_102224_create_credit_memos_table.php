<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('credit_memos', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->uuid("personel_id");
            $table->uuid("dealer_id")->comment("origin and destination must have same dealer");
            $table->uuid("origin_id")->comment("settle only");
            $table->uuid("destination_id")->comment("unsettel only or settle but same origin");
            $table->date("date");
            $table->string("status");
            $table->string("tax_invoice")->nullable();
            $table->double("total", 20, 2);
            $table->string("reason");
            $table->string("number");
            $table->integer("number_order");
            $table->string("note")->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign("personel_id")
                ->references("id")
                ->on("personels")
                ->onUpdate("cascade");
            
                $table->foreign("dealer_id")
                ->references("id")
                ->on("dealers")
                ->onUpdate("cascade");

            $table->foreign("origin_id")
                ->references("id")
                ->on("invoices")
                ->onDelete("cascade");

            $table->foreign("destination_id")
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
        Schema::dropIfExists('credit_memos');
    }
};
