<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('menu_sub_handlers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("menu_id");
            $table->string("title")->nulllable();
            $table->string("icon")->nulllable();
            $table->tinyInteger("visibility")->default(1);
            $table->string("screen")->nulllable();
            $table->text("note")->nulllable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign("menu_id")
                ->references("id")
                ->on("menu_handlers")
                ->onDelete("cascade");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('menu_sub_handlers');
    }
};
