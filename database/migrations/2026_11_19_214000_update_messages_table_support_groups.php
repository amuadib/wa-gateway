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
            $table->string('number', 100)->change();
            if (!Schema::hasColumn('messages', 'is_group')) {
                $table->boolean('is_group')->default(false)->after('number');
            }
            if (!Schema::hasColumn('messages', 'sender')) {
                $table->string('sender', 100)->nullable()->after('is_group');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('number', 20)->change();
            if (Schema::hasColumn('messages', 'is_group')) {
                $table->dropColumn('is_group');
            }
            if (Schema::hasColumn('messages', 'sender')) {
                $table->dropColumn('sender');
            }
        });
    }
};
