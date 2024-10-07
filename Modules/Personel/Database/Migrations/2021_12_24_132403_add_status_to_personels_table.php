<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusToPersonelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('personels', function (Blueprint $table) {
            $table->after("resign_date", function($table){
                $table->double("target", 15, 2)->nullable();
                $table->enum("status", ["1", "2", "3"])->default("1")->comment("1 => active, 2 => freeze, 3 => inactive");
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
        Schema::table('personels', function (Blueprint $table) {
            $table->dropColumn('target');
            $table->dropColumn('status');
        });
    }
}
