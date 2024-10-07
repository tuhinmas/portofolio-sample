<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddHandoverStatusFiledToDealersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dealers', function (Blueprint $table) {
            $table->after("note", function($table){
                $table->uuid("handover_status")->nullable();
            });
            $table->foreign("handover_status")
                  ->references("id")
                  ->on("handovers")
                  ->onUpdate("cascade");
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
            $table->dropForeign(['handover_status']);
            $table->dropColumn('handover_status');
        });
    }
}
