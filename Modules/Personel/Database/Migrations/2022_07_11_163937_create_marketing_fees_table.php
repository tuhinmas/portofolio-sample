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
        Schema::create('marketing_fee', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->primary("id");
            $table->uuid("personel_id");
            $table->double("fee_reguler_total", 12, 2)->nullable();
            $table->double("fee_reguler_settle", 12, 2)->nullable();
            $table->double("fee_target_total", 12, 2)->nullable();
            $table->double("fee_target_settle", 12, 2)->nullable();
            $table->year("year");
            $table->tinyInteger("quarter");
            $table->timestamps();
            $table->softDeletes();

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
        Schema::dropIfExists('marketing_fee');
    }
};
