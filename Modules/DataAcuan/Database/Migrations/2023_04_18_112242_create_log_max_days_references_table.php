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
        Schema::create('log_max_days_references', function (Blueprint $table) {
            $table->id();
            $table->uuid("personel_id");
            $table->uuid("max_days_reference_id");
            $table->timestamps();
            $table->foreign("personel_id")
                ->references("id")
                ->on("personels")
                ->onDelete("cascade");
                
            $table->foreign("max_days_reference_id")
                ->references("id")
                ->on("max_days_references")
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
        Schema::dropIfExists('log_max_days_references');
    }
};
