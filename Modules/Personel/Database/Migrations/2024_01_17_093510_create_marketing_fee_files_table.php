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
        Schema::create('marketing_fee_files', function (Blueprint $table) {
            $table->uuid("id");
            $table->primary("id");
            $table->uuid("marketing_fee_payment_id");
            $table->text("link");
            $table->string("caption")->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign("marketing_fee_payment_id")
                ->references("id")
                ->on("marketing_fee_payments")
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
        Schema::dropIfExists('marketing_fee_files');
    }
};
