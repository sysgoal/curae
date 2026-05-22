<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Anamnesis extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    // Diz ao Laravel para converter automaticamente o JSON do banco para Array no PHP
    protected $casts = [
        'symptoms_checklist' => 'array',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function professional()
    {
        return $this->belongsTo(User::class, 'professional_id');
    }
}