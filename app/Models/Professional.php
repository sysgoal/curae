<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Professional extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'cpf',
        'phone',
        'profession',
        'specialty',
        'council_type',
        'council_number',
        'council_state',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Retorna o usuário de login vinculado a este profissional (se houver).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Retorna as consultas agendadas para este profissional.
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}