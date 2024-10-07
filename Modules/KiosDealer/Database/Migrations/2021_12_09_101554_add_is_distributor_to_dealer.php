<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsDistributorToDealer extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dealers', function (Blueprint $table) {
            $table->after("status_color", function ($table) {
                $table->boolean("is_distributor")->default(0);
            });
        });
        if (Schema::hasColumn('dealers', 'change_status')) {
            Schema::table('dealers', function (Blueprint $table) {
                $table->dropColumn('change_status');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dealers', function (Blueprint $table) {
            $table->dropColumn('is_distributor');
        });
    }
}
