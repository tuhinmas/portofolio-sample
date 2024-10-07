<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSecondTelephoneToDealersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dealers', function (Blueprint $table) {
            $table->after("telephone", function($table){
                $table->string("second_telephone")->nullable();
            });
        });

        Schema::table('dealers', function (Blueprint $table) {
            $table->after("last_grading", function($table){
                $table->string("bank_account_number")->nullable();
                $table->string("bank_name")->nullable();
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
        Schema::table('dealers', function (Blueprint $table) {
            $table->dropColumn('second_telephone');
            $table->dropColumn('bank_account_number');
            $table->dropColumn('bank_name');
        });
    }
}
