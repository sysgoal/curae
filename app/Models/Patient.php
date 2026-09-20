<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'cpf', 'rg', 'date_of_birth', 'gender', 
        'phone', 'email', 'emergency_contact_name', 'emergency_contact_phone',
        'zip_code', 'street', 'number', 'complement', 'neighborhood', 'city', 'state',
        'blood_type', 'last_anamnesis_at'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'last_anamnesis_at' => 'datetime',
    ];

    public function anamneses()
    {
        return $this->hasMany(Anamnesis::class);
    }

    /**
     * Um paciente tem muitas Evoluções.
     */
    public function evolutions()
    {
        return $this->hasMany(Evolution::class);
    }

    /**
     * Um paciente tem muitas Receitas / Prescrições.
     */
    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    /**
     * Um paciente tem muitos Exames/Ficheiros anexados.
     */
    public function files()
    {
        return $this->hasMany(PatientFile::class);
    }

    public function examRequests()
    {
        return $this->hasMany(ExamRequest::class);
    }
}
