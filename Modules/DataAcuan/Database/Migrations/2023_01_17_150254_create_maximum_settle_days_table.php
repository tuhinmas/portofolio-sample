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
        Schema::create('maximum_settle_days', function (Blueprint $table) {
            $table->uuid("id")->unique()->primary();
            $table->uuid("personel_id");
            $table->string("max_settle_for");
            $table->tinyInteger("days");
            $table->year("year");
            $table->timestamps();
            $table->softDeletes();
            $table->foreign("personel_id")
                ->references("id")
                ->on("personels");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('maximum_settle_days');
    }
};
