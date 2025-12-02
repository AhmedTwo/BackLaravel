<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Optionnel mais bonne pratique

class Apply extends Model
{
    use HasFactory;

    /**
     * Spécifie la table de la base de données associée à ce modèle.
     * Si le nom n'était pas 'applies' (convention Laravel), cela serait obligatoire.
     * Pour 'applys', il est prudent de le définir explicitement.
     */
    protected $table = 'applys';

    /**
     * Les attributs qui peuvent être assignés massivement (mass assignable).
     * Ces champs sont ceux que nous créons dans le contrôleur (Apply::create).
     */
    protected $fillable = [
        'offer_id',
        'user_id',
        'status', // 'pending' par défaut dans la migration
    ];

    /**
     * Relation: Une candidature (Apply) appartient à une offre (Offer).
     */
    public function offer()
    {
        return $this->belongsTo(Offer::class, 'offer_id');
    }

    /**
     * Relation: Une candidature (Apply) appartient à un utilisateur (User).
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
