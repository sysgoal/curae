<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleBlock extends Model
{
    use HasFactory;

    protected $fillable = ['professional_id', 'start_time', 'end_time', 'reason'];

    public function professional()
    {
        return $this->belongsTo(Professional::class);
    }
}