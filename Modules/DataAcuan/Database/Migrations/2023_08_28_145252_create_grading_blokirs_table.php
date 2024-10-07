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
        Schema::create('grading_blocks', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->primary("id");
            
            $table->bigInteger('grading_id')->unsigned();
            $table->foreign("grading_id")
                ->references("id")
                ->on("gradings");
            $table->uuid("personel_id");
            $table->foreign("personel_id")
                ->references("id")
                ->on("personels");

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
        Schema::dropIfExists('grading_blokirs');
    }
};
