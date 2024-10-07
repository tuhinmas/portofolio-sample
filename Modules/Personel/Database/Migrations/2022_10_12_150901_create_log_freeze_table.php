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
        Schema::create('log_freeze', function (Blueprint $table) {
            $table->uuid('id')->unique();
            $table->primary('id');
            $table->uuid("personel_id");
            $table->foreign("personel_id")
                ->references("id")
                ->on("personels")
                ->onDelete("cascade");

            $table->date('freeze_start');
            $table->date('freeze_end');
            $table->tinyInteger('after_freeze')->default("0");
            
            $table->uuid("id_subtitute_personel")->nullable();
            $table->foreign("id_subtitute_personel")
                ->references("id")
                ->on("personels")
                ->onDelete("cascade");

            $table->uuid("user_id")->nullable();
            $table->foreign("user_id")
                ->references("id")
                ->on("users")
                ->onDelete("cascade");
            
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
        Schema::dropIfExists('log_freeze');
    }
};
