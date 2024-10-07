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
        Schema::table('log_marketing_fee_counter', function (Blueprint $table) {
            $table->tinyInteger("is_settle")->after("type")->default("0");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('log_marketing_fee_counter', function (Blueprint $table) {
            $table->dropColumn('is_settle');
        });
    }
};
