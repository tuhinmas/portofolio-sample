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
        Schema::create('marketing_fee_payments', function (Blueprint $table) {
            $table->uuid("id");
            $table->primary("id");
            $table->uuid("personel_id");
            $table->uuid("marketing_fee_id");
            $table->tinyInteger("status");
            $table->double("amount", 20, 2);
            $table->string("reference_number")->nullable();
            $table->timestamp("date");
            $table->text("note");
            $table->timestamps();
            $table->softDeletes();
            $table->foreign("personel_id")
                ->references("id")
                ->on("personels")
                ->onDelete("cascade");
                
            $table->foreign("marketing_fee_id")
                ->references("id")
                ->on("marketing_fee")
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
        Schema::dropIfExists('marketing_fee_payments');
    }
};
