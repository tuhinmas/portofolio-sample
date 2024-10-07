<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGradingIdToDealersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dealers', function (Blueprint $table) {
            $table->after("entity_id", function($table){
                $table->unsignedBigInteger("grading_id")->nullable();
                $table->foreign("grading_id")
                      ->references("id")
                      ->on("gradings");
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
        Schema::table('dealers', function (Blueprint $table) {
            $table->dropForeign(['grading_id']);
            $table->dropColumn('grading_id');
        });
    }
}
