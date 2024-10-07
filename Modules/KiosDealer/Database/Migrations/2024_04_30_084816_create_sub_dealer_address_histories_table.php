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
        Schema::create('sub_dealer_address_histories', function (Blueprint $table) {
            $table->id()->unique();
            $table->string('type');
            $table->uuid("sub_dealer_data_history_id")->nullable();
            $table->uuid('parent_id')->nullable();
            $table->uuid('province_id');
            $table->uuid('city_id');
            $table->uuid('district_id');

            $table->foreign("parent_id")
                ->references("id")
                ->on("sub_dealers");
            $table->foreign("sub_dealer_data_history_id")
                ->references("id")
                ->on("sub_dealer_data_histories");


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
        Schema::dropIfExists('sub_dealer_address_histories');
    }
};
