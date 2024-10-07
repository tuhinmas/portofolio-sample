<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSoftDelete extends Migration
{
    private $tables = [
        'divisions',
        'religions',
        'positions',
        'banks',
        'bussiness_sector_categories',
        'bussiness_sectors','products',
        'packages',
        'agency_levels',
        'prices',
        'countries'
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
     * add sof delete column to all data acuan tables
     *
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
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->drop_softDelete_column($this->tables);
    }

    /**
     * drop all soft delete column from DataAcuan module or use for rollback
     *
     * @param [type] $table
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
