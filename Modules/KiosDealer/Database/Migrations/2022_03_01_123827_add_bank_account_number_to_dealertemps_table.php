<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBankAccountNumberToDealertempsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dealer_temps', function (Blueprint $table) {
            $table->after("bank_account_number", function($table){
                $table->string("bank_account_name")->nullable();
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
            $table->dropColumn('bank_account_name');
        });
    }
}
