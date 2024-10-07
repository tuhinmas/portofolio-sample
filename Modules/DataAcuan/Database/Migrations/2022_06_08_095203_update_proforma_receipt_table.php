<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
            $table->after("logo_link", function($table){
                $table->string("image_header_link")->nullable();
                $table->string("image_footer_link")->nullable();
                $table->string("note_receving")->nullable();
                $table->string("note_sop")->nullable();
                $table->enum("receipt_for",["1", "2", "3", "4"])->comment("1 => proforma, 2 => invoice, 3 => dispatch order, 4 => delivery order")->default(1);
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
        Schema::table('proforma_receipts', function (Blueprint $table) {
            $table->dropColumn('image_header_link');
            $table->dropColumn('image_footer_link');
            $table->dropColumn('note_receving');
            $table->dropColumn('note_sop');
            $table->dropColumn('receipt_for');
        });   
    }
};
