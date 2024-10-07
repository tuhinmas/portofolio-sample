<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $index_exists = collect(DB::select("SHOW INDEXES FROM users"))->pluck('Key_name')->contains('users_email_unique');
            if ($index_exists) {
                $table->dropUnique("users_email_unique");
            }
            
            $table->unique(['email','deleted_at'], 'users_email_unique_active')->whereNull('deleted_at');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_email_unique_active');
            $table->unique('email', 'users_email_unique');
        });
    }
};
