<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class Prescription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'professional_id',
        'appointment_id',
        'medications',
        'orientations',
        'valid_until',
        'verification_code',
        'is_signed_digitally',
    ];

    protected $casts = [
        'medications' => 'array', // O Laravel converte o JSON do banco para Array automaticamente
        'valid_until' => 'date',
        'is_signed_digitally' => 'boolean',
    ];

    /**
     * Evento disparado automaticamente ao criar uma nova prescrição.
     * Gera um código de verificação único.
     */
    protected static function booted()
    {
        static::creating(function ($prescription) {
            if (empty($prescription->verification_code)) {
                // Gera um código no formato CURAE-XXXX-XXXX
                $prescription->verification_code = 'CURAE-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));
            }
        });
    }

    /**
     * Gera o link seguro para enviar ao paciente.
     * Opcional: Você pode colocar um tempo de expiração na rota assinada.
     */
    public function getShareableLinkAttribute()
    {
        // Esse método presume que você criará uma rota chamada 'prescriptions.show.public'
        return URL::signedRoute('prescriptions.show.public', ['prescription' => $this->id]);
    }

    // --- Relacionamentos ---

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