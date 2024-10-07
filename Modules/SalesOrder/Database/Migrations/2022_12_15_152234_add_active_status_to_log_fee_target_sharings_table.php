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
        Schema::table('fee_target_sharing_so_origins', function (Blueprint $table) {
            $table->tinyInteger("is_active")->after("is_returned")->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fee_target_sharing_so_origins', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
