<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablePaymementMethodInvoices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         if (Schema::hasColumn('payments','payment_method_id')){
            Schema::table('payments', function (Blueprint $table) {
                $table->dropForeign('payments_payment_method_id_foreign');
                $table->dropColumn('payment_method_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
