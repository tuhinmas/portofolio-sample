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
        Schema::create('dealer_change_histories', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->uuid("dealer_id");
            $table->uuid("dealer_temp_id");
            $table->dateTime("submited_at")->nullable();
            $table->uuid("submited_by")->nullable();
            $table->uuid("confirmed_by")->nullable();
            $table->dateTime("confirmed_at")->nullable();
            $table->dateTime("approved_at")->nullable();
            $table->uuid("approved_by")->nullable();
            
            $table->foreign("dealer_id")
                ->references("id")
                ->on("dealers");

            $table->foreign("dealer_temp_id")
                ->references("id")
                ->on("dealer_temps");
            
            $table->foreign("submited_by")
                ->references("id")
                ->on("personels");

            $table->foreign("confirmed_by")
                ->references("id")
                ->on("personels");

            $table->foreign("approved_by")
                ->references("id")
                ->on("personels");


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
        Schema::dropIfExists('dealer_change_histories');
    }
};
