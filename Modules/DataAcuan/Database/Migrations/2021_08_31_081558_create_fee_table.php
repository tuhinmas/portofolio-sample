<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFeeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fee', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->primary("id");
            $table->uuid("product_id");
            $table->integer("taget");
            $table->integer("fee_per_item");
            $table->bigInteger("total");
            $table->timestamps();
            $table->softDeletes();
            $table->foreign("product_id")
                  ->references("id")
                  ->on('products')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fee');
    }
}
