<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class User extends Model
{
    use HasFactory, HasApiTokens;

    protected $table = 'users';

    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'password',
        'role',
        'telephone',
        'ville',
        'code_postal',
        'cv_pdf',
        'qualification',
        'preference',
        'disponibilite',
        'photo',
        'company_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Un user peut faire plusieurs demandes
    public function requests()
    {
        return $this->hasMany(Request::class, 'user_id');
    }

    // Un user peut appartenir à une compagnie
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    // Pour les apply
    public function applies()
    {
        return $this->hasMany(Apply::class, 'user_id');
    }
}
