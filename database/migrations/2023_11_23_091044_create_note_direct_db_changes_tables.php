<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNoteDirectDbChangesTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('note_direct_db_changes')){
            Schema::create('note_direct_db_changes', function (Blueprint $table) {
                $table->id();
                $table->string("change_by");
                $table->text("note");
                $table->timestamps();
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
        if (Schema::hasTable('note_direct_db_changes')){
            Schema::dropIfExists('note_direct_db_changes');
        }
    }
}
