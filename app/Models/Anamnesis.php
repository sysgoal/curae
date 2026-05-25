<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Anamnesis extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'professional_id',
        'type',
        'chief_complaint',
        'family_history',
        'patient_routine',
        'symptoms_checklist',
        'child_data'
    ];

    protected $casts = [
        'symptoms_checklist' => 'array',
        'child_data' => 'array',
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