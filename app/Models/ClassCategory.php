<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    // 1. මේ කැටගරි එකට අදාල පන්ති මොනවද? (Ex: Primary -> 1-A, 1-B, 2-A...)
    public function sections()
    {
        return $this->hasMany(Section::class);
    }

    // 2. මේ කැටගරි එකට අදාල වෙලාවල් මොනවද? (Ex: Primary -> 07:50 - 08:20)
    public function periodTimings()
    {
        return $this->hasMany(PeriodTiming::class)->orderBy('period_number');
    }
}
