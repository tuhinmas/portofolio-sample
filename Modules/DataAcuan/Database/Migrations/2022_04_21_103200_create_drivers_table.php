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
        Schema::create('drivers', function (Blueprint $table) {
            $table->uuid('id')->unique();
            $table->primary('id');
            $table->string("transportation_type");
            $table->string("police_number");
            $table->string("id_driver");
            $table->foreign("id_driver")
                ->references("id")
                ->on("personels")
                ->onDelete("cascade");
            $table->string("capacity");
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
        Schema::dropIfExists('drivers');
    }
};
