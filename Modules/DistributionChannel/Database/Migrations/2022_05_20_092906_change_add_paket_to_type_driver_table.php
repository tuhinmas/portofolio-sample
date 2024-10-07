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
        Schema::table('discpatch_order', function (Blueprint $table) {
            \DB::statement("ALTER TABLE `discpatch_order` CHANGE `type_driver` `type_driver` enum('internal','external','paket');");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('discpatch_order', function (Blueprint $table) {
            \DB::statement("ALTER TABLE `discpatch_order` CHANGE `type_driver` `type_driver` enum('internal','external','paket');");
        });
    }
};
