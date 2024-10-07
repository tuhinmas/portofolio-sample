<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DeleteColumnAgencyLevelIdInSubDealersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('sub_dealers', 'agency_level_id')) {
            Schema::table('sub_dealers', function (Blueprint $table) {
                $table->dropForeign(['agency_level_id']);
                $table->dropColumn('agency_level_id');
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
