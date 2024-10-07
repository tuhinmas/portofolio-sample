<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStoreTempsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('store_temps', function (Blueprint $table) {
            $table->uuid('id')->unique();
            $table->primary('id');
            $table->uuid('personel_id')->nullable();
            $table->string('name');
            $table->text('address');
            $table->string('telephone');
            $table->string('gmaps_link')->nullable();
            $table->enum('status',['rejected','accepted','submission of changes','filed'])->default('filed');
            $table->enum('status_color',['c2c2c2','faa30c','ff0000'])->default('c2c2c2');
            $table->uuid('agency_level_id')->nullable();
            $table->longText("note")->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('personel_id')
                  ->references('id')
                  ->on('personels')
                  ->onUpdate("cascade");

            $table->foreign('agency_level_id')
                  ->references('id')
                  ->on('agency_levels')
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
        Schema::dropIfExists('store_temps');
    }
}
