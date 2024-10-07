<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChangeFieldPersonelsTableAgain extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('personels')->delete();
        Schema::table('personels', function (Blueprint $table) {
            $table->dropForeign(['supervisor_id']);
        });

        Schema::table('personels', function (Blueprint $table) {
            $table->foreign('supervisor_id')
                ->references('id')
                ->on('personels')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Schema::table('personels', function (Blueprint $table) {
        //     $table->dropForeign(['supervisor_id']);
        // });
    }
}
