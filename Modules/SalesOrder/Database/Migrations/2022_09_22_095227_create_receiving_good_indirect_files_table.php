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
        Schema::create('receiving_good_indirect_files', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->primary("id");
            $table->string("caption",225);
            $table->uuid("receiving_good_id");
            $table->string("attachment");
            $table->string("attachment_status");
            $table->timestamps();
            $table->softDeletes();
            $table->foreign("receiving_good_id")
                  ->references("id")
                  ->on("receiving_good_indirect_sales")
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
        Schema::dropIfExists('receiving_good_indirect_files');
    }
};
