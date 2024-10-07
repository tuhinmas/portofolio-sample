<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePointProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('point_products', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->primary("id");
            $table->year("year");
            $table->uuid("product_id");
            $table->integer("minimum_quantity");
            $table->integer("point");
            $table->timestamps();
            $table->softDeletes();
            $table->foreign("product_id")
                ->references("id")
                ->on("products")
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
        Schema::dropIfExists('point_products');
    }
}
