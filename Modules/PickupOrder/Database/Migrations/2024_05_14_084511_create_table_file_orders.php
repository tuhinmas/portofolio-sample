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
        Schema::create('pickup_order_detail_files', function (Blueprint $table) {
            $table->uuid("id")->unique()->primary();
            $table->uuid("pickup_order_detail_id");

            $table->foreign('pickup_order_detail_id')
                ->references('id')
                ->on('pickup_order_details')
                ->onDelete('cascade');

            $table->enum('type', ["up","down"])->nullable();
            $table->string('attachment')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pickup_order_files', function (Blueprint $table) {
            $table->uuid("id")->unique()->primary();
            $table->uuid("pickup_order_detail_id");

            $table->foreign('pickup_order_detail_id')
                ->references('id')
                ->on('pickup_order_details')
                ->onDelete('cascade');

            $table->string('caption')->nullable();
            $table->string('attachment')->nullable();
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
        Schema::dropIfExists('pickup_order_detail_files');
        Schema::dropIfExists('pickup_order_files');
    }
};
