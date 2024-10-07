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
        Schema::create('dealer_file_histories', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->uuid("dealer_data_history_id")->nullable();
            $table->uuid("dealer_id");
            $table->string('file_type');
            $table->string('data')->nullable();

            $table->foreign("dealer_data_history_id")
                ->references("id")
                ->on("dealer_data_histories");

            $table->foreign("dealer_id")
                ->references("id")
                ->on("dealers");
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
        Schema::dropIfExists('dealer_file_histories');
    }
};
