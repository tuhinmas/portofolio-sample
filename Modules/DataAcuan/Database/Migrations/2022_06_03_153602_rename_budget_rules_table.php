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
        Schema::table('budget_rules', function (Blueprint $table) {
            $table->dropForeign(['id_budget']);
        });

        Schema::rename('budget_rules', 'budget_areas');

        Schema::table('budget_areas', function (Blueprint $table) {
            $table->foreign("id_budget")
                ->references("id")
                ->on("budgets")
                ->onDelete("cascade");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
