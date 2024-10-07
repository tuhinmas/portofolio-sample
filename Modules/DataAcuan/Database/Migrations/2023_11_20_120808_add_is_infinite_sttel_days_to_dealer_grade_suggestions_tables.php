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
        Schema::table('dealer_grade_suggestions', function (Blueprint $table) {
            $table->boolean("is_infinite_settle_days")->after("valid_from")->default(false);
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
            $table->dropColumn('is_infinite_settle_days');
        });
    }
};
