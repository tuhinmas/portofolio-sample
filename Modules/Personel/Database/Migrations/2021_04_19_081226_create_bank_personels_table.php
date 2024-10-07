<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBankPersonelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bank_personels', function (Blueprint $table) {
            $table->uuid('id')->unique();
            $table->uuid('personel_id');
            $table->uuid('bank_id');
            $table->string('branch');//cabang
            $table->string('owner');
            $table->string('rek_number');
            $table->string('swift_code');
            $table->timestamps();
            $table->foreign('bank_id')
                  ->references('id')
                  ->on('banks')
                  ->onDelete('cascade');

            $table->foreign('personel_id')
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
        Schema::dropIfExists('bank_personels');
    }
}
