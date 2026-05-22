<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\URL;

class Feedback extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'appointment_id',
        'patient_id',
        'rating',
        'comments',
        'is_anonymous',
        'answered_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_anonymous' => 'boolean',
        'answered_at' => 'datetime', // Converte para o objeto Carbon
    ];

    /**
     * Gera o link seguro para o paciente preencher a avaliação.
     */
    public function getFormLinkAttribute()
    {
        // Rota assinada para garantir que apenas quem tem o link consiga avaliar, 
        // e apenas para esta consulta específica.
        return URL::signedRoute('feedbacks.edit.public', ['feedback' => $this->id]);
    }

    // --- Relacionamentos ---

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
