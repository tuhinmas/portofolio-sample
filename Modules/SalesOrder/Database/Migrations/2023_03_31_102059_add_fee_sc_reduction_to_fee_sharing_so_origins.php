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
        Schema::table('fee_sharing_so_origins', function (Blueprint $table) {
            $table->double("sc_reduction_percentage")->after("fee_percentage")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fee_sharing_so_origins', function (Blueprint $table) {
            $table->dropColumn('sc_reduction_percentage');
        });
    }
};
