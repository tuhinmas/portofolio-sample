<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fee_target_sharing_so_origins', function (Blueprint $table) {
            $table->dropColumn('fee_nominal');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fee_target_sharing_so_origins', function (Blueprint $table) {
            $table->after("quantity_unit", function ($table) {
                $table->double("fee_nominal", 20, 2);
            });
        });
    }
};
