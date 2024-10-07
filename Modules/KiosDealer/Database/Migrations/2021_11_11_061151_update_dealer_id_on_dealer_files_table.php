<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateDealerIdOnDealerFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dealer_files', function (Blueprint $table) {
            $table->dropForeign(['dealer_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dealer_files', function (Blueprint $table) {
            $table->foreign("dealer_id")
                  ->references("id")
                  ->on("dealers")
                  ->onDelete("cascade");
        });
    }
}
