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
        Schema::create('prize_marketings', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->primary("id");
            $table->year("year")->nullable();
            $table->string("prize")->nullable();
            $table->integer("poin")->nullable();
            $table->string("code")->nullable();
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
        Schema::dropIfExists('prize_marketings');
    }
};
