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
        'blood_type'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];
}