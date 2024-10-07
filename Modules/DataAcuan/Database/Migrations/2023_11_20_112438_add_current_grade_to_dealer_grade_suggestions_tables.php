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
        Schema::table('dealer_grade_suggestions', function (Blueprint $table) {
            $table->unsignedBigInteger("suggested_grading_id")->after("grading_id")->nullable();
            $table->foreign("suggested_grading_id")
                ->references("id")
                ->on("gradings");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dealer_grade_suggestions', function (Blueprint $table) {
            $table->dropForeign(['suggested_grading_id']);
            $table->dropColumn('suggested_grading_id');
        });
    }
};
