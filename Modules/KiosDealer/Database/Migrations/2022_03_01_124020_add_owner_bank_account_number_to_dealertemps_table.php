<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOwnerBankAccountNumberToDealertempsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dealer_temps', function (Blueprint $table) {
            $table->after("bank_name", function ($table) {
                $table->string("owner_bank_account_number")->nullable();
                $table->string("owner_bank_account_name")->nullable();
                $table->string("owner_bank_name")->nullable();
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
            $table->dropColumn('owner_bank_account_number');
            $table->dropColumn('owner_bank_account_name');
            $table->dropColumn('owner_bank_name');
        });
    }
}
