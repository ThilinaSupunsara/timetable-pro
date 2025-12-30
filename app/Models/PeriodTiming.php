<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeriodTiming extends Model
{
   use HasFactory;

    protected $fillable = [
        'class_category_id', 'period_number', 'start_time', 'end_time',
        'is_break', 'label'
    ];

    public function classCategory()
    {
        return $this->belongsTo(ClassCategory::class);
    }
}
