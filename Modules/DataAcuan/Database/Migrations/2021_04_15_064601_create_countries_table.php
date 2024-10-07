<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCountriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->uuid('id')->unique();
            $table->primary('id');
            $table->string('code', 2);
            $table->unique('code');
            $table->string('code3', 3)->nullable();
            $table->string('codeNumeric', 4)->nullable();
            $table->string('domain', 4)->nullable();
            $table->string('label_nl', 75);
            $table->string('label_en', 75)->nullable();
            $table->string('label_de', 75)->nullable();
            $table->string('label_es', 75)->nullable();
            $table->string('label_fr', 75)->nullable();
            $table->string('postCode', 75)->nullable();
            $table->boolean('active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('countries');
    }
}
