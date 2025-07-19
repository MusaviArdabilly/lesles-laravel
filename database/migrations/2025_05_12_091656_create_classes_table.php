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
            $table->string('name'); // e.g., "Fisika 7A"
            $table->enum('type', ['privat', 'grup'])->default('privat');
            $table->foreignId('education_level_id')->constrained('education_levels')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('teacher_id')->nullable()->constrained('users')->onDelete('set null');           
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->enum('status', ['pending', 'approved', 'rejected', 'assigned'])->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->json('schedules')->nullable();
            $table->json('members_id')->nullable();
            $table->text('description')->nullable();
            $table->text('note')->nullable();
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
