<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bailey_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('bailey_sessions', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('group_id');
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            }
        });

        // Assign existing devices to the first admin user so existing devices are not orphaned
        $adminId = DB::table('users')->where('role', 'admin')->value('id');
        if ($adminId) {
            DB::table('bailey_sessions')->whereNull('user_id')->update(['user_id' => $adminId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bailey_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('bailey_sessions', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });
    }
};
