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
        Schema::table('fee_target_sharing_so_origins', function (Blueprint $table) {
            $table->uuid("marketing_id")->after("id")->nullable();
            $table->foreign("marketing_id")
                ->references("id")
                ->on("personels");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fee_target_sharing_so_origins', function (Blueprint $table) {
            $table->dropForeign(['marketing_id']);
            $table->dropColumn('marketing_id');
        });
    }
};
