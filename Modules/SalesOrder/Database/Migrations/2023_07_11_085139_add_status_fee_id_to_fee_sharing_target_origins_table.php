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
        Schema::table('fee_target_sharings', function (Blueprint $table) {
            $table->after("product_id", function ($table) {
                $table->uuid("status_fee_id");
                $table->unsignedBigInteger("sharing_id")->nullable();
                $table->double("status_fee_percentage", 5, 2);
                $table->year("year");
                $table->integer("quarter");
                $table->double("total_fee", 20, 2)->default(0);
                $table->double("fee_percentage", 5, 2)->default(0);
                $table->double("fee_shared", 20, 2)->default(0);
                $table->foreign("status_fee_id")
                    ->references("id")
                    ->on("status_fee");

                $table->foreign("sharing_id")
                    ->references("id")
                    ->on("fee_target_sharings");
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
        Schema::table('fee_target_sharings', function (Blueprint $table) {
            $table->dropForeign(['status_fee_id']);
            $table->dropForeign(['sharing_id']);
            $table->dropColumn('status_fee_id');
            $table->dropColumn('sharing_id');
            $table->dropColumn('status_fee_percentage');
            $table->dropColumn('year');
            $table->dropColumn('quarter');
            $table->dropColumn('total_fee');
            $table->dropColumn('fee_percentage');
            $table->dropColumn('fee_shared');
        });
    }
};
