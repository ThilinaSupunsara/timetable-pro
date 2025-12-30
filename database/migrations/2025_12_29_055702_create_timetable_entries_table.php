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
        Schema::create('timetable_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('teacher_id')->nullable()->constrained()->onDelete('cascade');

            $table->integer('day_of_week'); // 1=Monday, 2=Tuesday...
            $table->integer('period_number'); // 1, 2, 3...

            // එක පන්තියකට එක වෙලාවක දෙකක් බැහැ
            $table->unique(['section_id', 'day_of_week', 'period_number']);

            // එක ගුරුවරයෙක්ට එක වෙලාවක දෙකක් බැහැ
            $table->unique(['teacher_id', 'day_of_week', 'period_number']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timetable_entries');
    }
};
