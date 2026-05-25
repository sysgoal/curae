<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Anamnesis extends Model
{
    use HasFactory;

    // A lista de campos que o Laravel pode preencher
    protected $fillable = [
        'patient_id',
        'professional_id',
        'type',
        'chief_complaint',
        'family_history',
        'patient_routine',     // <-- Esta causou o erro
        'symptoms_checklist',
        'child_data',
        'adult_data'           // <-- Garantir que esta também está cá!
    ];

    // Diz ao Laravel para converter automaticamente JSON em Array
    protected $casts = [
        'symptoms_checklist' => 'array',
        'child_data' => 'array',
        'adult_data' => 'array',
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