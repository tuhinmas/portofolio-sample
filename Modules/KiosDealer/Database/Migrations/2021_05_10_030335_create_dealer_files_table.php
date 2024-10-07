<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDealerFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->createDealerFile();
    }
    private function createDealerFile(){
        Schema::create('dealer_files', function (Blueprint $table) {
            $table->uuid('id')->unique();
            $table->primary('id');
            $table->uuid('dealer_id');
            $table->string('file_type');
            $table->string('data')->nullable();
            $table->timestamps();
            $table->foreign('dealer_id')
                  ->references('id')
                  ->on('dealers')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dealer_files');
    }
}
