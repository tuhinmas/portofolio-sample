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
        Schema::table('dealer_grade_suggestions', function (Blueprint $table) {
            $table->dropForeign(["payment_method_id"]);
            $table->dropColumn('payment_method_id');

            $table->json("payment_methods")->after("proforma_count")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dealer_grade_suggestions', function (Blueprint $table) {
            $table->uuid("payment_method_id")->after("grading_id")->nullable();
            $table
                ->foreign("payment_method_id")
                ->references("id")
                ->on("payment_methods");

                $table->dropColumn('payment_methods');
        });
    }
};
