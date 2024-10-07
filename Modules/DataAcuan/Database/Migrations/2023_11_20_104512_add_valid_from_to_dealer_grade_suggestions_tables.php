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
            $table->timestamp("valid_from")->after("grading_id")->comment("tanggal rules saran grading mulai berlaku")->nullable();
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
            $table->dropColumn('valid_from');
        });
    }
};
