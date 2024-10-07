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
        Schema::table('sub_dealers', function (Blueprint $table) {
            $table->after("dealer_id", function ($table) {
                $table->dateTime("closed_at")->nullable();
                $table->uuid("closed_by")->nullable();
                $table->foreign('closed_by')
                    ->references('id')
                    ->on('personels');
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
        Schema::table('sub_dealers', function (Blueprint $table) {
            $table->dropColumn(['closed_at']);
            $table->dropForeign(['closed_by']);
            $table->dropColumn('closed_by');
        });
    }
};
