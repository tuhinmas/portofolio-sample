<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateStatusOnStore extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('store_temps', function (Blueprint $table) {
            \DB::statement("ALTER TABLE `store_temps` CHANGE `status` `status` enum('filed','submission of changes','filed rejected','change rejected','draft') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft'");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('store_temps', function (Blueprint $table) {
            \DB::statement("ALTER TABLE `store_temps` CHANGE `status` `status` enum('filed','submission of changes','filed rejected','change rejected','draft') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft'");
        });
    }
}
