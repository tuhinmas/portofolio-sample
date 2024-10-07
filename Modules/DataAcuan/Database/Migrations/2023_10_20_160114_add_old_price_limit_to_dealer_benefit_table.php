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
        Schema::table('dealer_benefits', function (Blueprint $table) {
            $table->after("agency_level_id", function ($table) {
                $table->boolean("old_price_usage")->default(0);
                $table->boolean("old_price_usage_limit")->nullable();
                $table->boolean("old_price_days_limit")->nullable();
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dealer_benefits', function (Blueprint $table) {
            $table->dropColumn('old_price_usage');
            $table->dropColumn('old_price_usage_limit');
            $table->dropColumn('old_price_days_limit');
        });
    }
};
