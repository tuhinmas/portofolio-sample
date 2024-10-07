<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBgGradienToGradingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('gradings', function (Blueprint $table) {
            $table->after("fore_color", function($table){
                $table->boolean("bg_gradien")->default("0");
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
            $table->dropColumn('bg_gradien');
        });
    }
}
