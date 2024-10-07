<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeFieldDealersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    { 
        DB::table('dealers')->delete();
        Schema::table('dealers', function (Blueprint $table) {
            if(Schema::hasColumn('stores', 'status_color')){
                $table->dropColumn('status_color');
            }            
        });
        Schema::table('dealers', function (Blueprint $table) {
            $table->after('status', function($table){
                $table->enum('status_color',['c2c2c2','f78800','ff0000','000000'])->default('c2c2c2');
            });           
        });

        Schema::table('dealers', function (Blueprint $table) {
            if(Schema::hasColumn('dealers', 'user_id')){
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');          
            }
        });
        Schema::table('dealers', function (Blueprint $table) {
            if (!Schema::hasColumn('dealers', 'personel_id')) {
                $table->after('id', function($table){
                    $table->uuid('personel_id');
                    $table->foreign('personel_id')
                        ->references('id')
                        ->on('personels')
                        ->onDelete('cascade');
                });
            }           
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dealers', function (Blueprint $table) {
            $table->dropColumn('personel_id');
        });
    }
}
