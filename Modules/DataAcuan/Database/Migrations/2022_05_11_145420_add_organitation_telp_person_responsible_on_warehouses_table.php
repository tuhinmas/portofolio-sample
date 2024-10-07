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
        Schema::table('warehouses', function (Blueprint $table) {
            $table->after("name", function($table) {  
                $table->uuid('id_organisation');
                $table->foreign('id_organisation')
                    ->references('id')
                    ->on('organisations')
                    ->onDelete('cascade');
                $table->string('telp');
                $table->uuid('personel_id');
                $table->foreign('personel_id')
                    ->references('id')
                    ->on('personels')
                    ->onDelete('cascade');
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
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropForeign(['id_organisation']);
            $table->dropColumn('telp');
            $table->dropForeign(['personel_id']);
        });
    }
};
