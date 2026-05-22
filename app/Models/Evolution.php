<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Evolution extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'professional_id',
        'appointment_id',
        'weight',
        'height',
        'bmi',
        'systolic_bp',
        'diastolic_bp',
        'heart_rate',
        'respiratory_rate',
        'temperature',
        'oxygen_saturation',
        'blood_glucose',
        'clinical_notes',
    ];

    protected $casts = [
        'weight' => 'float',
        'height' => 'float',
        'bmi' => 'float',
        'temperature' => 'float',
    ];

    /**
     * Acessor (Mutator) para pegar a pressão arterial formatada (Ex: 120/80)
     * Pode ser acessado na view usando: $evolution->blood_pressure
     */
    public function getBloodPressureAttribute()
    {
        if ($this->systolic_bp && $this->diastolic_bp) {
            return "{$this->systolic_bp}/{$this->diastolic_bp}";
        }
        return null;
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function professional()
    {
        return $this->belongsTo(Professional::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
    
}