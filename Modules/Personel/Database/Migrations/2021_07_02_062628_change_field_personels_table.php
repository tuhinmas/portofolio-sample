<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeFieldPersonelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('personels')->delete();
        Schema::table('personels', function (Blueprint $table) {
            // if (!Schema::hasColumn('personels', 'identity_card_type')) {
            $table->after('organisation_id', function ($table) {
                $table->unsignedBigInteger('identity_card_type')->change();
                $table->foreign('identity_card_type')
                    ->references('id')
                    ->on('identity_cards')
                    ->onDelete('cascade');
            });
            // }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Schema::table('personels', function (Blueprint $table) {
        //     $table->dropForeign(['identity_card_type']);
        //     $table->dropColumn('identity_card_type');
        // });
    }
}
