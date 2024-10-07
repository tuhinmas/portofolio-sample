<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('budget_areas', function (Blueprint $table) {            
            $table->dropForeign(['id_budget']);
            $table->dropColumn('id_budget');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('budget_areas', function (Blueprint $table) {
            $table->uuid("id_budget");
            $table->foreign("id_budget")
                ->references("id")
                ->on("budgets")
                ->onDelete("cascade");
        });
    }
};
