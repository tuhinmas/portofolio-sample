<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBussinessOrganisationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bussiness_organisations', function (Blueprint $table) {
            $table->id();
            $table->uuid('bussiness_sector_id');
            $table->uuid('organisation_id');
            $table->timestamps();
            $table->foreign('bussiness_sector_id')
                  ->references('id')
                  ->on('bussiness_sectors')
                  ->onDelete('cascade');
            $table->foreign('organisation_id')
                  ->references('id')
                  ->on('organisations')
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
        Schema::dropIfExists('bussiness_organisations');
    }
}
