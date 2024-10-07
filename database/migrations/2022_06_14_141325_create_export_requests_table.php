<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExportRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('export_requests', function (Blueprint $table) {
            $table->uuid('id')->unique();
            $table->primary('id');
            $table->string("type", 225)->nullable();
            $table->enum("status",["requested","ready","onprogress"])->default("requested");
            $table->string("link", 225)->nullable();
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
        Schema::dropIfExists('export_requests');
    }
}
