<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bailey_sessions', function (Blueprint $table) {
            $table->timestamp('disconnected_at')
                ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bailey_sessions', function (Blueprint $table) {
            $table->dropColumn('disconnected_at');
        });
    }
};
