<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDistributorIdToSubDealersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('sub_dealers', 'distributor_id')) {
            Schema::table('sub_dealers', function (Blueprint $table) {
                $table->after("personel_id", function ($table) {
                    $table->uuid("distributor_id")->nullable();
                    $table->foreign("distributor_id")
                        ->references("id")
                        ->on("dealers")
                        ->onDelete("cascade")
                        ->onUpdate("cascade");
                });
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('sub_dealers', 'distributor_id')) {
            Schema::table('sub_dealers', function (Blueprint $table) {
                $table->dropForeign(['distributor_id']);
                $table->dropColumn('distributor_id');
            });
        }
    }
}
