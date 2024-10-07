<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeBankNameToBankIdOnDalersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dealers', function (Blueprint $table) {
            $table->renameColumn('bank_name', 'bank_id');
        });
        
        Schema::table('dealers', function (Blueprint $table) {
            $table->renameColumn('owner_bank_name', 'owner_bank_id');
        });

        Schema::table('dealers', function (Blueprint $table) {
            $table->uuid("bank_id")->change();
            $table->foreign("bank_id")
                  ->references("id")
                  ->on("banks")
                  ->onDelete("cascade");
        });
        
        Schema::table('dealers', function (Blueprint $table) {
            $table->uuid("owner_bank_id")->change();
            $table->foreign("owner_bank_id")
                  ->references("id")
                  ->on("banks")
                  ->onDelete("cascade");
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
            $table->dropForeign(['bank_id']);
            $table->dropForeign(['owner_bank_id']);
        }); 

        Schema::table('dealers', function (Blueprint $table) {
            $table->renameColumn('bank_id','bank_name');
            $table->renameColumn('owner_bank_id','owner_bank_name');
        });  
    }
}
