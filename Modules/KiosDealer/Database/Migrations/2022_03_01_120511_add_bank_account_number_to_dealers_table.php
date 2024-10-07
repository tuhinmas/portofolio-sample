<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBankAccountNumberToDealersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dealers', function (Blueprint $table) {
            $table->after("bank_name", function($table){
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
        Schema::table('dealers', function (Blueprint $table) {
            if (Schema::hasColumn('dealers', 'owner_bank_account_number')) {
                $table->dropColumn('owner_bank_account_number');
            }
            if (Schema::hasColumn('dealers', 'owner_bank_account_name')) {
                $table->dropColumn('owner_bank_account_name');
            }
            if (Schema::hasColumn('dealers', 'owner_bank_name')) {
                $table->dropColumn('owner_bank_name');
            }
        });
    }
}
