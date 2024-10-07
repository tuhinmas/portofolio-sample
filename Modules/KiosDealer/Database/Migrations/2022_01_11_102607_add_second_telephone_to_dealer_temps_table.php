<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSecondTelephoneToDealerTempsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dealer_temps', function (Blueprint $table) {
            $table->after("telephone", function($table){
                $table->string("second_telephone")->nullable();
            });
        });

        Schema::table('dealer_temps', function (Blueprint $table) {
            $table->after("sub_dealer_id", function($table){
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
        Schema::table('dealer_temps', function (Blueprint $table) {
            $table->dropColumn('second_telephone');
            $table->dropColumn('bank_account_number');
            $table->dropColumn('bank_name');
        });
    }
}
