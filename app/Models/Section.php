<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use HasFactory;

    protected $fillable = ['class_category_id', 'grade', 'class_name'];

    // පන්තියේ සම්පූර්ණ නම ගන්න (Ex: 10 - A) - මෙය අපිට View එකේදී ලේසියි
    public function getFullNameAttribute()
    {
        return $this->grade . ' - ' . $this->class_name;
    }

    // සම්බන්ධතා
    public function classCategory()
    {
        return $this->belongsTo(ClassCategory::class);
    }

    public function allocations()
    {
        return $this->hasMany(Allocation::class);
    }

    public function timetableEntries()
    {
        return $this->hasMany(TimetableEntry::class);
    }
}
