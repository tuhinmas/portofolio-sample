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
        Schema::create('price_histories', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->primary("id");
            $table->double("price");
            $table->uuid("price_id")->nullable();
            $table->foreign("price_id")
                ->references("id")
                ->on("prices")
                ->onUpdate("cascade");
            $table->uuid("product_id")->nullable();
            $table->foreign("product_id")
                ->references("id")
                ->on("products")
                ->onUpdate("cascade");
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
        Schema::dropIfExists('price_histories');
    }
};
