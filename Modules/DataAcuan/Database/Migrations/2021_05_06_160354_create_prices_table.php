<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prices', function (Blueprint $table) {
            $table->uuid('id')->unique();
            $table->primary('id');
            $table->uuid('product_id');
            $table->uuid('agency_level_id');
            $table->integer('het');
            $table->integer('price');
            $table->integer('minimum_order');
            $table->timestamps();
            $table->foreign('product_id')
                  ->references('id')
                  ->on('products')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
            $table->foreign('agency_level_id')
                  ->references('id')
                  ->on('agency_levels')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

                  
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('prices');
    }
}
