<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePersonelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('personels', function (Blueprint $table) {
            $table->uuid('id')->unique();
            $table->primary('id');
            $table->string('name');
            $table->uuid('supervisor_id')->nullable();
            $table->uuid('position_id');
            $table->text('ttl');
            $table->uuid('religion_id');
            $table->string('gender');
            $table->uuid('citizenship');
            $table->uuid('organisation_id');
            $table->string('identity_card_type');
            $table->string('identity_number');
            $table->string('npwp');
            $table->string('blood_group');
            $table->uuid('user_id')->nullable();
            $table->timestamps();
            $table->foreign('position_id')
                  ->references('id')
                  ->on('positions')
                  ->onDelete('cascade');
            $table->foreign('religion_id')
                  ->references('id')
                  ->on('religions')
                  ->onDelete('cascade');
            $table->foreign('organisation_id')
                  ->references('id')
                  ->on('organisations')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->foreign('supervisor_id')
                  ->references('id')
                  ->on('positions')
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
        Schema::dropIfExists('personels');
    }
}
