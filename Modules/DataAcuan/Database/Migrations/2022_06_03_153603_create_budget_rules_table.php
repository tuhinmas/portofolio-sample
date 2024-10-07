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
        Schema::create('budget_rules', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->primary("id");
            $table->string("type_budget")->nullable();
            $table->integer("max_budget")->nullable();
            $table->uuid('id_event');
            $table->foreign("id_event")
                ->references("id")
                ->on("event_types")
                ->onDelete("cascade");

            $table->uuid('id_budget_area');
            $table->foreign("id_budget_area")
                ->references("id")
                ->on("budget_areas")
                ->onDelete("cascade");

            $table->uuid('id_budget');
            $table->foreign("id_budget")
                ->references("id")
                ->on("budgets")
                ->onDelete("cascade");

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('budget_rules');
    }
};
