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
        Schema::create('personel_supervisor_histories', function (Blueprint $table) {
            $table->id();
            $table->uuid("personel_id");
            $table->uuid("position_id");
            $table->uuid("supervisor_id");
            $table->timestamp("change_at")->nullable();
            $table->uuid("modified_by")->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign("personel_id")
                ->references("id")
                ->on("personels");

            $table->foreign("supervisor_id")
                ->references("id")
                ->on("personels");

            $table->foreign("position_id")
                ->references("id")
                ->on("positions");

            $table->foreign("modified_by")
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
        Schema::dropIfExists('personel_supervisor_histories');
    }
};
