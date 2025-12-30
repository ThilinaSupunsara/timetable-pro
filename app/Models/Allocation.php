<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Allocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'section_id', 'subject_id', 'teacher_id',
        'periods_per_week', 'consecutive_periods',
        'is_fixed_slot', 'fixed_day', 'fixed_period'
    ];

    public function section() { return $this->belongsTo(Section::class); }
    public function subject() { return $this->belongsTo(Subject::class); }
    public function teacher() { return $this->belongsTo(Teacher::class); }
}
