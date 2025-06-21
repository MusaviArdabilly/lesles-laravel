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
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('level', ['sd', 'smp', 'sma']);
            // $table->enum('status', ['active', 'inactive', 'holiday']);
            $table->string('subject');
            $table->foreignId('teacher_id')->nullable()->constrained('users')->onDelete('set null');
            $table->json('schedule');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
