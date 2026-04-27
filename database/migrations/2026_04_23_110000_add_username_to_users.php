<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 60)->nullable()->unique()->after('name');
        });

        // Backfill: derive username from the email local part for existing users.
        foreach (DB::table('users')->get() as $user) {
            DB::table('users')->where('id', $user->id)->update([
                'username' => strtolower(explode('@', $user->email)[0]),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
