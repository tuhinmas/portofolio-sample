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
        Schema::table('log_freeze', function (Blueprint $table) {
            $table->date('freeze_end')->nullable()->change();
            $table->string('after_freeze')->default("1")->nullable()->change();
            $table->softDeletes()->after("updated_at");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('log_freeze', function (Blueprint $table) {
            $table->dropColumn('deleted_at');
        });
    }
};
