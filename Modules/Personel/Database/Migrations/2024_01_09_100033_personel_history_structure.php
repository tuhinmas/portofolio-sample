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
        Schema::create('personnel_structure_histories', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->primary("id");
            $table->date("start_date")->nullable();
            $table->date("end_date")->nullable();
            $table->uuid("personel_id");
            $table->uuid("rmc_id")->nullable();
            $table->uuid("asst_mdm_id")->nullable();
            $table->uuid("mdm_id")->nullable();
            $table->uuid("mm_id")->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign("personel_id")
                ->references("id")
                ->on("personels")
                ->onDelete("cascade");

            $table->foreign("rmc_id")
                ->references("id")
                ->on("personels")
                ->onDelete("cascade");
                
            $table->foreign("asst_mdm_id")
                ->references("id")
                ->on("personels")
                ->onDelete("cascade");

            $table->foreign("mdm_id")
                ->references("id")
                ->on("personels")
                ->onDelete("cascade");

            $table->foreign("mm_id")
                ->references("id")
                ->on("personels")
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
        //
    }
};
