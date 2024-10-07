<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusTransferedToStoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stores', function (Blueprint $table) {
            \DB::statement("ALTER TABLE `stores` CHANGE `status` `status` enum('rejected','accepted','submission of changes','filed', 'transfered');");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stores', function (Blueprint $table) {
            \DB::statement("ALTER TABLE `stores` CHANGE `status` `status` enum('rejected','accepted','submission of changes','filed');");
        });
    }
}
