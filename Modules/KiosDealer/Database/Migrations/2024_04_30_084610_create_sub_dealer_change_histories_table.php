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
        Schema::create('sub_dealer_change_histories', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->uuid("sub_dealer_id");
            $table->uuid("sub_dealer_temp_id");
            $table->dateTime("submited_at")->nullable();
            $table->uuid("submited_by")->nullable();
            $table->uuid("confirmed_by")->nullable();
            $table->dateTime("confirmed_at")->nullable();
            $table->dateTime("approved_at")->nullable();
            $table->uuid("approved_by")->nullable();
            
            $table->foreign("sub_dealer_id")
                ->references("id")
                ->on("sub_dealers");

            $table->foreign("sub_dealer_temp_id")
                ->references("id")
                ->on("sub_dealer_temps");
            
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
        Schema::dropIfExists('sub_dealer_change_histories');
    }
};
