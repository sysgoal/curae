<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Anamnesis extends Model
{
    use HasFactory, SoftDeletes;

    // A tabela no banco se chama 'anamneses', mas às vezes o Laravel tenta pluralizar 
    // 'anamnesis' para 'anamnesises'. Forçar o nome da tabela evita dores de cabeça.
    protected $table = 'anamneses';

    protected $fillable = [
        'patient_id',
        'appointment_id',
        'professional_id',
        'chief_complaint',
        'history_present_illness',
        'past_medical_history',
        'family_history',
        'social_history',
        'allergies',
        'current_medications',
        'physical_examination',
        'diagnostic_hypothesis',
        'conduct_plan',
    ];

    /**
     * Retorna o paciente dono deste histórico.
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Retorna a consulta em que esta anamnese foi registrada.
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Retorna o profissional que registrou a anamnese.
     */
    public function professional()
    {
        return $this->belongsTo(Professional::class);
    }
}