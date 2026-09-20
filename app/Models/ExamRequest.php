<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id', 
        'professional_id', 
        'exams', 
        'clinical_indication', 
        'notes'
    ];

    protected $casts = [
        'exams' => 'array',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function professional()
    {
        return $this->belongsTo(Professional::class);
    }
}