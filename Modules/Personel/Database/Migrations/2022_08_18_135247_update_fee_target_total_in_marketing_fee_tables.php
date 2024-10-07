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
        Schema::table('marketing_fee', function (Blueprint $table) {
            $table->decimal("fee_reguler_total", 20, 2)->change();
            $table->decimal("fee_reguler_settle", 20, 2)->change();
            $table->decimal("fee_target_total", 20, 2)->change();
            $table->decimal("fee_target_settle", 20, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('', function (Blueprint $table) {

        });
    }
};
