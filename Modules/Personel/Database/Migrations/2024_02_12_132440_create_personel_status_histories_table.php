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
        Schema::create('personel_status_histories', function (Blueprint $table) {
            $table->uuid("id");
            $table->primary("id");
            $table->date("start_date")->nullable();
            $table->date("end_date")->nullable();
            $table->enum("status",["1","2","3"])->default("1");
            $table->uuid("personel_id");
            $table->uuid("change_by")->nullable();
            $table->foreign("personel_id")
                ->references("id")
                ->on("personels")
                ->onDelete("cascade");
            $table->foreign("change_by")
                ->references("id")
                ->on("personels")
                ->onDelete("cascade");
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
        Schema::dropIfExists('personel_status_histories');
    }
};
