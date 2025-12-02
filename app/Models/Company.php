<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $table = 'companys';

    protected $fillable = [
        'name',
        'logo',
        'number_of_employees',
        'industry',
        'address',
        'latitude',
        'longitude',
        'description',
        'email_company',
        'n_siret',
        'status',
    ];

    public function offers()
    {
        return $this->hasMany(Offer::class, 'id_company');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'company_id');
    }
}
