<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoice_proformas', function (Blueprint $table) {
             $table->renameColumn('personel_id', 'issued_by');

             $table->renameIndex('invoice_proformas_personel_id_foreign', 'invoice_proformas_issued_by_foreign');
         
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoice_proformas', function (Blueprint $table) {
            $table->renameColumn('issued_by', 'personel_id');

            // Ubah nama foreign key (jika perlu) kembali ke semula
            $table->renameIndex('invoice_proformas_issued_by_foreign', 'invoice_proformas_personel_id_foreign');
        
        });
    }
};
