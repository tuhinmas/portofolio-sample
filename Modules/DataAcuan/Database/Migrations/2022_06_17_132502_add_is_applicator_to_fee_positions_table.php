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
        Schema::table('fee_positions', function (Blueprint $table) {
            $table->tinyInteger("is_applicator")->after("fee_as_marketing")->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fee_positions', function (Blueprint $table) {
            $table->dropColumn('is_applicator');
        });
    }
};
