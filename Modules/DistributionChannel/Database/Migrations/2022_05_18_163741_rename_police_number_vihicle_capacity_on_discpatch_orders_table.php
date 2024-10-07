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
        Schema::table('discpatch_order', function (Blueprint $table) {
            $table->renameColumn('police_number', 'armada_identity_number');
            $table->renameColumn('vihicle_capacity', 'dispatch_order_weight');
            $table->renameColumn('armada_phone_number', 'driver_phone_number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('discpatch_order', function (Blueprint $table) {
            $table->renameColumn('police_number', 'armada_identity_number');
            $table->renameColumn('vihicle_capacity', 'dispatch_order_weight');
            $table->renameColumn('armada_phone_number', 'driver_phone_number');
        });
    }
};
