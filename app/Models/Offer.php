<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    use HasFactory;

    protected $table = 'offers';

    protected $fillable = [
        'title',
        'description',
        'mission',
        'location',
        'category',
        'employment_type_id',
        'technologies_used',
        'benefits',
        'participants_count',
        'image_url',
        'id_company',
    ];

    public function employment_type()
    {
        return $this->belongsTo(Employment_type::class, 'employment_type_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'id_company');
    }

    public function applies()
    {
        return $this->hasMany(Apply::class, 'offer_id');
    }
}
