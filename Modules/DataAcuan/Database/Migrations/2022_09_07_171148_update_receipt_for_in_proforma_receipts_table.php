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
        Schema::table('proforma_receipts', function (Blueprint $table) {
            DB::statement("ALTER TABLE proforma_receipts CHANGE `receipt_for` `receipt_for`ENUM('1','2','3','4','5'
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1'");
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('proforma_receipts', function (Blueprint $table) {
            DB::statement("ALTER TABLE proforma_receipts CHANGE `receipt_for` `receipt_for`ENUM('1','2','3','4'
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1'");
        });
    }
};
