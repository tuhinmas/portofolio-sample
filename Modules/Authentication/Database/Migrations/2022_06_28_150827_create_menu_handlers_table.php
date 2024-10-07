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
        Schema::create('menu_handlers', function (Blueprint $table) {
            $table->id();
            $table->string("title")->nulllable();
            $table->string("icon")->nulllable();
            $table->tinyInteger("visibility")->default(1);
            $table->string("role")->nulllable();
            $table->string("screen")->nulllable();
            $table->text("note")->nulllable();
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
        Schema::dropIfExists('menu_handlers');
    }
};
