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
        Schema::table('receiving_goods', function (Blueprint $table) {
            $table->enum("delivery_status", ['1','2'])->default("1")->after("date_received")->comment("1 => draft, 2 => received");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('receiving_goods', function (Blueprint $table) {
            $table->dropColumn('delivery_status');
        });
    }
};
