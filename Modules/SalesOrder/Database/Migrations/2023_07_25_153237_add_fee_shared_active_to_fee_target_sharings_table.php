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
            $table->dropForeign(['sharing_id']);
            $table->dropColumn('sharing_id');
        });
      
        Schema::table('fee_target_sharings', function (Blueprint $table) {
            $table->after("fee_shared", function($table){
                $table->double("fee_shared_active", 20,2)->deafult(0);
                $table->double("fee_shared_active_pending", 20,2)->deafult(0);
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
            $table->unsignedBigInteger("sharing_id")->after("status_fee_id")->nullable();
            $table->foreign("sharing_id")
                ->references("id")
                ->on("fee_target_sharings");
        });
      
        Schema::table('fee_target_sharings', function (Blueprint $table) {
            $table->dropColumn('fee_shared_active');
            $table->dropColumn('fee_shared_active_pending');
        });
    }
};
