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
            $table->unsignedBigInteger('group_id')
                ->after('id')
                ->default(1);
            $table->foreign('group_id')->references('id')->on('groups');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bailey_sessions', function (Blueprint $table) {
            $table->dropForeign('bailey_sessions_group_id_foreign');
            $table->dropColumn('group_id');
        });
    }
};
