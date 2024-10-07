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
        Schema::create('budget_provinces', function (Blueprint $table) {
            $table->uuid('id')->unique();
            $table->primary('id');

            $table->uuid("id_budget_rule");
            $table->foreign("id_budget_rule")
                ->references("id")
                ->on("budget_rules")
                ->onDelete("cascade");

            $table->uuid("province_id");
            $table->foreign("province_id")
                ->references("id")
                ->on("indonesia_provinces")
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
        Schema::dropIfExists('budget_provinces');
    }
};
