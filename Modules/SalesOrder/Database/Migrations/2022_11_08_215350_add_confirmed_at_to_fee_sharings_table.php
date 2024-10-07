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
        Schema::table('fee_sharings', function (Blueprint $table) {
            $table->timeStamp("confirmed_at")->after("fee_status")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fee_sharings', function (Blueprint $table) {
            $table->dropColumn('confirmed_at');
        });
    }
};
