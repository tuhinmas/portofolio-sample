<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSoftDeleteToOrganization extends Migration
{
    private $tables = [
        'holdings',
        'entities',
        'categories',
        'organisations',
        'category_organisations',
        'bussiness_organisations'
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->add_softDelete_column($this->tables);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->drop_softDelete_column($this->tables);
    }

    /**
     * add soft delete columns to all KiosDealer migration tables
     *
     * @param [type] $tables
     * @return void
     */
    public function add_softDelete_column($tables){
        foreach($tables as $oneTable){
            Schema::table($oneTable, function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * drop all soft delete columns
     *
     * @param [type] $tables
     * @return void
     */
    public function drop_softDelete_column($tables){
        foreach($tables as $oneTable){
            Schema::table($oneTable, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
}
