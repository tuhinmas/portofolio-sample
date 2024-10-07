<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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
            DB::statement("ALTER TABLE discpatch_order CHANGE `status` `status`ENUM('planned', 'delivered','canceled','received') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'planned'");
        });

        Schema::table('dispatch_promotions', function (Blueprint $table) {
            DB::statement("ALTER TABLE dispatch_promotions CHANGE `status` `status`ENUM('planned', 'delivered','canceled','received') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'planned'");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
