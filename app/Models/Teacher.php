<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    protected $fillable = ['name', 'short_code'];

    // ගුරුවරයාට අදාල විෂයන්
    public function subjects()
    {
        return $this->belongsToMany(Subject::class);
    }
}
