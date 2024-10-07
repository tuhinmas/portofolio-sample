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
        Schema::create('dealer_address_histories', function (Blueprint $table) {
            $table->id()->unique();
            $table->string('type');
            $table->uuid("dealer_data_history_id")->nullable();
            $table->uuid('parent_id')->nullable();
            $table->uuid('province_id');
            $table->uuid('city_id');
            $table->uuid('district_id');

            $table->foreign("parent_id")
                ->references("id")
                ->on("dealers");
            $table->foreign("dealer_data_history_id")
                ->references("id")
                ->on("dealer_data_histories");


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
        Schema::dropIfExists('dealer_address_histories');
    }
};
