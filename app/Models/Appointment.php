<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'professional_id',
        'appointment_date',
        'start_time',
        'end_time',
        'type',
        'status',
        'notes',
        'cancellation_reason'
    ];

    protected $casts = [
        'appointment_date' => 'date',
    ];

    /**
     * Retorna o paciente desta consulta.
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Retorna o profissional responsável pela consulta.
     */
    public function professional()
    {
        return $this->belongsTo(Professional::class);
    }

    /**
     * Retorna a anamnese preenchida nesta consulta (se houver).
     */
    public function anamnesis()
    {
        return $this->hasOne(Anamnesis::class);
    }

    /**
     * Retorna as prescrições geradas nesta consulta.
     */
    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }
}