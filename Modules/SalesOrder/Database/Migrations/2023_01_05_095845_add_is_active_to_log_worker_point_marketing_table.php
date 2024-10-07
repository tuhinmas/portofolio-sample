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
        Schema::table('log_worker_point_marketing', function (Blueprint $table) {
            $table->boolean("is_active")->after("is_count")->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('log_worker_point_marketing', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
