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
        Schema::create('sub_dealer_temp_notes', function (Blueprint $table) {
            $table->uuid('id')->unique();
            $table->primary('id');

            $table->uuid("sub_dealer_temp_id");
            $table->uuid("personel_id")->nullable();
            $table->text("note")->nullable();

            $table->foreign("sub_dealer_temp_id")
                ->references("id")
                ->on("sub_dealer_temps")
                ->onDelete("cascade");
                
            $table->foreign("personel_id")
                ->references("id")
                ->on("personels")
                ->onDelete("cascade");

            $table->string("status", 225)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sub_dealer_temp_notes');
    }
};
