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
        Schema::table('messages', function (Blueprint $table) {
            $table->string('key', 32)
                ->nullable()
                ->after('sent');
            $table->string('status')
                ->nullable()
                ->after('key');
            $table->string('error')
                ->nullable()
                ->after('status');
            $table->index('key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['key', 'status', 'error']);
        });
    }
};
