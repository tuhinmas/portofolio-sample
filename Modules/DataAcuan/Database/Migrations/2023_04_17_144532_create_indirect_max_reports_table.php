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
        Schema::create('max_days_references', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->primary("id");
            $table->integer("year");
            $table->tinyInteger("maximum_days_for");
            $table->integer("maximum_days");
            $table->string("description")->nullable();
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
        Schema::dropIfExists('max_days_references');
    }
};
