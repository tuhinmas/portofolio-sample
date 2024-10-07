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
        Schema::table('personel_status_histories', function (Blueprint $table) {
            $table->boolean("is_new")->default(0);
            $table->boolean("is_checked")->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('personel_status_histories', function (Blueprint $table) {
            $table->dropColumn('is_new');
            $table->dropColumn('is_checked');
        });
    }
};
