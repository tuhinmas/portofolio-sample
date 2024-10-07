<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReceivingGoodFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('receiving_good_files', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->primary("id");
            $table->uuid("receiving_good_id");
            $table->string("attachment");
            $table->enum("attachment_status", ["confirm", "report"]);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign("receiving_good_id")
                  ->references("id")
                  ->on("receiving_goods")
                  ->onDelete("cascade");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('receiving_good_files');
    }
}
