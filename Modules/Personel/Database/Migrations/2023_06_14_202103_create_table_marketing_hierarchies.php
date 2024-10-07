<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('marketing_hierarchies', function (Blueprint $table) {
            $table->id();
            $table->string("marketing");
            $table->date("from")->nullable();
            $table->date("until")->nullable();
            $table->string("sales")->nullable();
            $table->string("rmc")->nullable();
            $table->string("mdm")->nullable();
            $table->string("ast_mdm")->nullable();
            $table->string("mm")->nullable();
            $table->string("aplikator")->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('marketing_hierarchies');
    }
};
