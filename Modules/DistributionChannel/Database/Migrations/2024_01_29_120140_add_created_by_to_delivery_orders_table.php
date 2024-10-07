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
        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->after("confirmed_by" , function ($table) {
                $table->uuid("created_by")->nullable();
                $table->uuid("updated_by")->nullable();

                $table->foreign("created_by")
                    ->references("id")
                    ->on("personels")
                    ->onDelete("cascade");

                $table->foreign("updated_by")
                    ->references("id")
                    ->on("personels")
                    ->onDelete("cascade");

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
        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->dropForeign(["created_by"]);
            $table->dropForeign(["updated_by"]);

            $table->dropColumn('created_by');
            $table->dropColumn('updated_by');
        });
    }
};
