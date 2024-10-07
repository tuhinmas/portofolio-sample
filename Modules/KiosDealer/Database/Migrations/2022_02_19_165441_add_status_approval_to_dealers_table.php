<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusApprovalToDealersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dealer_temps', function (Blueprint $table) {
            \DB::statement("ALTER TABLE `dealer_temps` CHANGE `status` `status` enum('draft','filed','submission of changes','filed rejected','change rejected', 'wait approval');");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dealer_temps', function (Blueprint $table) {

        });
    }
}
