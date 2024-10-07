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
        Schema::table('fee_sharing_so_origins', function (Blueprint $table) {
            $table->double("total_fee", 20, 2)->after("fee_shared")->default(0);
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
            $table->dropColumn('total_fee');
        });
    }
};
