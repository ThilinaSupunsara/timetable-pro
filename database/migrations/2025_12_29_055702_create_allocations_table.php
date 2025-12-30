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
        Schema::create('allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('teacher_id')->nullable()->constrained()->onDelete('cascade'); // Fixed activities might not have a teacher

            $table->integer('periods_per_week'); // සතියට මුළු වාර ගණන (Ex: 5)

            // --- Advanced Features ---
            $table->integer('consecutive_periods')->default(1); // 1=Single, 2=Double Block
            $table->boolean('is_fixed_slot')->default(false); // ස්ථිර කාලච්ඡේදයක්ද? (Ex: Religion on Wed)
            $table->integer('fixed_day')->nullable(); // 1=Mon, 2=Tue... (If fixed)
            $table->integer('fixed_period')->nullable(); // 1-8 (If fixed)

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('allocations');
    }
};
