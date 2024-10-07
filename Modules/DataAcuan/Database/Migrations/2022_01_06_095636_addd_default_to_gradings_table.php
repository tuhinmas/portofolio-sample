<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AdddDefaultToGradingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('gradings', function (Blueprint $table) {
            $table->after("action", function($table){
                $table->tinyInteger("default")->default("0")->comment("default dealer grading if true");
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
        Schema::table('gradings', function (Blueprint $table) {
            $table->dropColumn('default');
        });
    }
}
