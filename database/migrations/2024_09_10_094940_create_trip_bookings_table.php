<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('trip_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');

            $table->string('from_address', 255);  
            $table->decimal('from_lat', 10, 5)->nullable(); 
            $table->decimal('from_lng', 10, 5)->nullable();  
        
            $table->string('to_address', 255); 
            $table->decimal('to_lat', 10, 5)->nullable();  
            $table->decimal('to_lng', 10, 5)->nullable();

            $table->datetime('scheduled_time')->nullable();
            $table->timestamp('from_time');
            $table->timestamp('to_time');
            $table->datetime('return_time')->nullable();
            $table->boolean('round_trip')->default(false);
            $table->integer('km');       
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->string('payment');
            $table->enum('trip_status', ['requested', 'accepted', 'completed', 'cancelled']);
            $table->enum('trip_type', ['airport', 'long_distance'])->default('airport');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_bookings');
    }
};
