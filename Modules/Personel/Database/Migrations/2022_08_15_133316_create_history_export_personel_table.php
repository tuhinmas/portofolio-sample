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
        Schema::create('history_export_personel', function (Blueprint $table) {
            $table->uuid('id')->unique();
            $table->primary('id');
            $table->string("type", 225)->nullable();
            $table->string("status")->default("ready");
            $table->string("link", 225)->nullable();
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
        Schema::dropIfExists('history_export_personel');
    }
};
