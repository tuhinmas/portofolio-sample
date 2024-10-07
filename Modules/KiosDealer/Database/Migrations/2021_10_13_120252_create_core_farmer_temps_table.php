<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCoreFarmerTempsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('core_farmer_temps', function (Blueprint $table) {
            $table->uuid('id')->unique();
            $table->primary('id');
            $table->string('name');
            $table->string('telephone');
            $table->text('address');
            $table->uuid('store_temp_id');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('store_temp_id')
                  ->references('id')
                  ->on('store_temps')
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
        Schema::dropIfExists('core_farmer_temps');
    }
}
