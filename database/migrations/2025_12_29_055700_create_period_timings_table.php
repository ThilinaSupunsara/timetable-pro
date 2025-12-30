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
        Schema::create('period_timings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_category_id')->constrained()->onDelete('cascade');
            $table->integer('period_number'); // 0 for Morning Assembly, 1-8 for periods
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_break')->default(false); // Interval එකක්ද?
            $table->string('label')->nullable(); // Ex: "Lunch Break"
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('period_timings');
    }
};
