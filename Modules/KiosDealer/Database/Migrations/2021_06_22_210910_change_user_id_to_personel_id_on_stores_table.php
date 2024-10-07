<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeUserIdToPersonelIdOnStoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stores', function (Blueprint $table) {
            if(Schema::hasColumn('stores', 'user_id')){
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
            $table->after('id', function($table){
                $table->uuid('personel_id');
            });
            $table->foreign('personel_id')
                  ->references('id')
                  ->on('personels')
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
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('personel_id');
        });
    }
}
