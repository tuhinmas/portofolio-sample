<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateStatusInDealersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dealers', function (Blueprint $table) {
            if (Schema::hasColumn('dealers', 'status_color')) {
                $table->dropColumn('status_color');
            }
        });
        Schema::table('dealers', function (Blueprint $table) {
            if (Schema::hasColumn('dealers', 'status')) {
                $table->dropColumn('status');
            }
        });

        if (!Schema::hasColumn('dealers', 'status')) {
            Schema::table('dealers', function (Blueprint $table) {
                $table->after('telephone', function ($table) {
                    $table->enum('status', ['rejected', 'accepted', 'submission of changes', 'filed', 'draft'])->default("draft");
                    $table->enum('status_color', ['c2c2c2', 'f78800', 'ff0000', '000000', '505050'])->default('505050');
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
        Schema::table('dealers', function (Blueprint $table) {

        });
    }
}
