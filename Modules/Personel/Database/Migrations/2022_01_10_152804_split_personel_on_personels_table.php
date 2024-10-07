<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SplitPersonelOnPersonelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('personels', function (Blueprint $table) {
            $table->renameColumn("ttl", "born_date");
        });

        Schema::table('personels', function (Blueprint $table) {
            $table->date("born_date")->change();
        });

        Schema::table('personels', function (Blueprint $table) {
            $table->after("position_id", function($table){
                $table->string("born_place")->nullable();
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
            $table->renameColumn('born_date', 'ttl');
            $table->dropColumn('born_place');
        });
    }
}
