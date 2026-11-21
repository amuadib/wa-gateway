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
            if (!Schema::hasColumn('messages', 'media_type')) {
                $table->string('media_type', 50)->nullable()->after('message');
            }
            if (!Schema::hasColumn('messages', 'media_path')) {
                $table->string('media_path', 255)->nullable()->after('media_type');
            }
            if (!Schema::hasColumn('messages', 'media_name')) {
                $table->string('media_name', 255)->nullable()->after('media_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $drop = [];
            if (Schema::hasColumn('messages', 'media_type')) {
                $drop[] = 'media_type';
            }
            if (Schema::hasColumn('messages', 'media_path')) {
                $drop[] = 'media_path';
            }
            if (Schema::hasColumn('messages', 'media_name')) {
                $drop[] = 'media_name';
            }
            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
