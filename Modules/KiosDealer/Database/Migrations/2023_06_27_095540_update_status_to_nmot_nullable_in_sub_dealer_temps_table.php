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
        Schema::table('sub_dealer_temps', function (Blueprint $table) {
            \DB::statement("ALTER TABLE `sub_dealer_temps` MODIFY `status` ENUM('draft','filed','submission of changes','filed rejected','change rejected','revised','revised change') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft';");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sub_dealer_temps', function (Blueprint $table) {

        });
    }
};
