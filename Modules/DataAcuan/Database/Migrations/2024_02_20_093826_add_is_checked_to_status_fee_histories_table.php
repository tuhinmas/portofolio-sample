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
        Schema::table('status_fee_histories', function (Blueprint $table) {
            $table->boolean("is_checked")->after("status_fee")->default(false);
        });
       
        Schema::table('fee_position_histories', function (Blueprint $table) {
            $table->boolean("is_checked")->after("fee_position")->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('status_fee_histories', function (Blueprint $table) {
            $table->dropColumn('is_checked');
        });
       
        Schema::table('fee_position_histories', function (Blueprint $table) {
            $table->dropColumn('is_checked');
        });
    }
};
