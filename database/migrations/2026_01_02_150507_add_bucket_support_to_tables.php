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
        // 1. Allocations table එකට bucket_name එකතු කිරීම
        Schema::table('allocations', function (Blueprint $table) {
            $table->string('bucket_name')->nullable()->after('subject_id');
            // උදා: 'AES_GRP' කියලා නමක් දුන්නොත් ඒ නම තියෙන ඔක්කොම එකට වැටෙන්නේ.
        });

        // 2. Timetable Entries වල Unique Constraint එක ඉවත් කිරීම
        Schema::table('timetable_entries', function (Blueprint $table) {
            // පන්තියකට එක වෙලාවක විෂයන් දෙකක් බැහැ කියන නීතිය අයින් කරනවා (Bucket නිසා)
            $table->dropUnique(['section_id', 'day_of_week', 'period_number']);
        });
    }

    public function down(): void
    {
        Schema::table('allocations', function (Blueprint $table) {
            $table->dropColumn('bucket_name');
        });

        // Reverse කරනකොට පරිස්සම් වෙන්න, data duplicate වෙලා තිබ්බොත් මේක error එයි.
        Schema::table('timetable_entries', function (Blueprint $table) {
            $table->unique(['section_id', 'day_of_week', 'period_number']);
        });
    }
};
