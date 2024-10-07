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
        Schema::create('log_marketing_fee_target_counter', function (Blueprint $table) {
            $table->id();
            $table->uuid("personel_id");
            $table->year("year");
            $table->tinyInteger("quarter");
            $table->timestamps();

            $table->foreign("personel_id")
                ->references("id")
                ->on("personels")
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
        Schema::dropIfExists('log_marketing_fee_target_counter');
    }
};
