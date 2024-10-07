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
        Schema::table('product_mandatories', function (Blueprint $table) {
            DB::statement("ALTER TABLE product_mandatories MODIFY COLUMN period_date YEAR DEFAULT 1990");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_mandatories', function (Blueprint $table) {
            $table->dropColumn('period_date');
        });
    }
};
