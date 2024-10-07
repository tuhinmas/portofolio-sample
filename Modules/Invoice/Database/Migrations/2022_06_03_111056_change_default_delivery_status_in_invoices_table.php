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
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('delivery_status');
        });
        
        Schema::table('invoices', function (Blueprint $table) {
            $table->enum("delivery_status", ["1", "2", "3"])->default("2")->after("date_delivery")->comment("1 => done, 2 => undone, 3 => consider done");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->enum("delivery_status", ["1", "2", "3"])->default("2")->after("date_delivery")->comment("1 => done, 2 => undone, 3 => consider done");
        });
    }
};
