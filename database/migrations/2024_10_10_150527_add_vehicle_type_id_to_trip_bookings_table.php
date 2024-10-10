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
        Schema::table('trip_bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('vehicle_type')->nullable();
            $table->foreign('vehicle_type')->references('id')->on('vehicle_types');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trip_bookings', function (Blueprint $table) {
            $table->dropForeign(['vehicle_type']);
            $table->dropColumn('vehicle_type');
        });
    }
};
