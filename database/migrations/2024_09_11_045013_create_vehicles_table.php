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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('driver_id');
            $table->string('vehicle_type', 50);
            $table->decimal('initial_starting_price', 10, 2);
            $table->decimal('rate_per_km', 5, 2);
            $table->string('license_plate', 20);
            $table->integer('seating_capacity');
            $table->string('image', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('driver_id')->references('id')->on('drivers')->onDelete('cascade');
        });

        
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
