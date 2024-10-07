<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->uuid('id')->unique();
            $table->primary('id');
            $table->uuid('user_id');
            $table->string('name');
            $table->text('address');
            $table->string('telephone');
            $table->string('gmaps_link')->nullable();
            $table->enum('status',['rejected','accepted','submission of changes','filed'])->default('filed');
            $table->enum('status_color',['c2c2c2','faa30c','ff0000'])->default('c2c2c2');
            $table->uuid('agency_level_id')->nullable();
            $table->timestamps();
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users');

            $table->foreign('agency_level_id')
                  ->references('id')
                  ->on('agency_levels')
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
        Schema::dropIfExists('stores');
    }
}
