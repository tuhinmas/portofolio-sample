<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCodeAccountTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ppn', function (Blueprint $table) {
            $table->after("period_date", function($table){
                $table->string("code_account")->nullable();
                $table->string("user_id")->nullable();
                $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ppn', function (Blueprint $table) {
            $table->dropColumn('code_account');
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            
        });
    }
}
