<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateStatusInSubDealersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('sub_dealers', 'status')) {
            Schema::table('sub_dealers', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }

        if (!Schema::hasColumn('sub_dealers', 'status')) {
            Schema::table('sub_dealers', function (Blueprint $table) {
                $table->after("telephone", function ($table) {
                    $table->enum("status", ["accepted", "submission of changes"])->default("accepted");
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
