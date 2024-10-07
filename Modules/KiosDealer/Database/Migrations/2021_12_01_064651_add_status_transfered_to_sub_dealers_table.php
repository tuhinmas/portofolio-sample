<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusTransferedToSubDealersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sub_dealers', function (Blueprint $table) {
            \DB::statement("ALTER TABLE `sub_dealers` CHANGE `status` `status` enum('accepted','submission of changes', 'transfered');");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sub_dealers', function (Blueprint $table) {
            \DB::statement("ALTER TABLE `sub_dealers` CHANGE `status` `status` enum('accepted','submission of changes');");
        });
    }
}
