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
        Schema::create('payment_day_colors', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->primary("id");
            $table->integer("min_days");
            $table->integer("max_days")->nullable();
            $table->string("bg_color")->default("FFFFFF");
            $table->string("text_color")->default("FFFFFF");
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
        Schema::dropIfExists('payment_day_colors');
    }
};
