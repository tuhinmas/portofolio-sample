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
        Schema::table('fee_positions', function (Blueprint $table) {
            $table->string("settle_from")->after("maximum_settle_days")->default("1")->comment("1 => settle from end of quarter, 2 => settle from order date confirmation");
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
            $table->dropColumn('settle_from');
        });
    }
};
