<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateStatusColorInSubDealersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('sub_dealers', 'status_color')) {
            Schema::table('sub_dealers', function (Blueprint $table) {
                $table->dropColumn('status_color');
            });
        }

        if (!Schema::hasColumn('sub_dealers', 'status_color')) {
            Schema::table('sub_dealers', function (Blueprint $table) {
                $table->after("status", function ($table) {
                    $table->enum("status_color", ["000000", "f78800"])->default("000000");
                });
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
    }
}
