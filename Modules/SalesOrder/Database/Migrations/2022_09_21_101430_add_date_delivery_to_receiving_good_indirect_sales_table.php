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
        Schema::table('receiving_good_indirect_sales', function (Blueprint $table) {
            $table->timestamp("date_received")->useCurrent()->after("note");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('receiving_good_indirect_sales', function (Blueprint $table) {
            $table->dropColumn('date_received');
        });
    }
};
