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
        Schema::create('sub_dealer_file_histories', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->uuid("sub_dealer_data_history_id")->nullable();
            $table->uuid("sub_dealer_id");
            $table->string('file_type');
            $table->string('data')->nullable();

            $table->foreign("sub_dealer_data_history_id")
                ->references("id")
                ->on("sub_dealer_data_histories");

            $table->foreign("sub_dealer_id")
                ->references("id")
                ->on("sub_dealers");
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
        Schema::dropIfExists('sub_dealer_file_histories');
    }
};
