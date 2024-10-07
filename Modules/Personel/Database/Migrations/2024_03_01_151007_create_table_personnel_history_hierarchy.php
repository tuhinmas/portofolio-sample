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
        Schema::create('personnel_hierarchy_histories', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();
            $table->uuid("subordinate_id");
            $table->foreign("subordinate_id")
                ->references("id")
                ->on("personels");
            $table->uuid("subordinate_position_id");
            $table->foreign("subordinate_position_id")
                ->references("id")
                ->on("positions");
            $table->uuid("supervisor_id")->nullable();
            $table->foreign("supervisor_id")
                ->references("id")
                ->on("personels");
            $table->uuid("supervisor_position_id");
            $table->foreign("supervisor_position_id")
                ->references("id")
                ->on("positions");
            $table->date('from');
            $table->date('until')->nullable();
            $table->longText('assignment_note')->nullable();
            $table->uuid('created_by');
            $table->foreign("created_by")
                ->references("id")
                ->on("personels");
            $table->uuid('updated_by');
            $table->foreign("updated_by")
                ->references("id")
                ->on("personels");
            $table->uuid('deleted_by')->nullable();
            $table->foreign("deleted_by")
                ->references("id")
                ->on("personels");
            $table->uuid('deleted_note')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('personnel_hierarchy_histories');
    }
};
