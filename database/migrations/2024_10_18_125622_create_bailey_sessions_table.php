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
        Schema::create('bailey_sessions', function (Blueprint $table) {
            $table->string('id');
            $table->primary('id');

            $table->string('event')
                ->nullable();
            $table->string('status')
                ->nullable();
            $table->longText('qr')
                ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bailey_sessions');
    }
};
