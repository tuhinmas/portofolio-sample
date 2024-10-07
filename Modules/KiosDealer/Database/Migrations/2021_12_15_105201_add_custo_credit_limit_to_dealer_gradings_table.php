<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustoCreditLimitToDealerGradingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dealer_gradings', function (Blueprint $table) {
            $table->dropColumn('grading_name');
        });

        Schema::table('dealer_gradings', function (Blueprint $table) {
            $table->after("dealer_id", function ($table) {
                $table->unsignedBigInteger('grading_id')->nullable(); 
                $table->foreign("grading_id")
                    ->references("id")
                    ->on("gradings")
                    ->onDelete("cascade");
            });
        });

        Schema::table('dealer_gradings', function (Blueprint $table) {
            $table->after("grading_id", function ($table) {
                $table->double("custom_credit_limit")->nullable();
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
        Schema::table('dealer_gradings', function (Blueprint $table) {
            $table->after("dealer_id", function ($table) {
                $table->double("grading_name")->nullable();
            });
            $table->dropForeign(['grading_id']);
            $table->dropColumn('custom_credit_limit');
        });
    }
}
