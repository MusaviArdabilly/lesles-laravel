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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('province_id')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->unsignedBigInteger('district_id')->nullable();
            $table->unsignedBigInteger('village_id')->nullable();
            $table->string('name')->nullable(); // for custom or display //SMA Negeri 2 Jombang
            $table->text('address')->nullable(); // for custom // Jl. Raya Jombang No. 1
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();

            // Foreign keys to laravolt/indonesia tables
            $table->foreign('province_id')->references('id')->on('indonesia_provinces')->nullOnDelete();
            $table->foreign('city_id')->references('id')->on('indonesia_cities')->nullOnDelete();
            $table->foreign('district_id')->references('id')->on('indonesia_districts')->nullOnDelete();
            $table->foreign('village_id')->references('id')->on('indonesia_villages')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
