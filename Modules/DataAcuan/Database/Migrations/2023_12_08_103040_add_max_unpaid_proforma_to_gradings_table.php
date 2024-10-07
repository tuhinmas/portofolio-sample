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
        Schema::table('gradings', function (Blueprint $table) {
            $table->integer("max_unsettle_proformas")->after("maximum_payment_days")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('gradings', function (Blueprint $table) {
            $table->dropColumn('max_unsettle_proformas');
        });
    }
};
