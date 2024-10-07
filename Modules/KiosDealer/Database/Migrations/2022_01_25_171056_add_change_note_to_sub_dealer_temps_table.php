<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddChangeNoteToSubDealerTempsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sub_dealer_temps', function (Blueprint $table) {
            $table->after("note", function($table){
                $table->text("change_note")->nullable();
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
        Schema::table('sub_dealer_temps', function (Blueprint $table) {
            $table->dropColumn('change_note');
        });
    }
}
