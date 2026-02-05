<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;

class Employe extends Model
{
    use HasFactory, Notifiable;

    protected $table = 'employe'; // Nom de la table
    protected $primaryKey = 'id'; // 'id' est auto-incrémenté
    public $timestamps = false; // Désactiver les timestamps automatiques

    protected $fillable = [
        'firstName', 'lastName', 'email', 'matricule', 'direction','isDeleted'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected static function boot()
    {
        parent::boot();

        // Suppression de l'UUID car nous utilisons un id auto-incrémenté
        // Si tu as besoin de soft delete, tu peux l'ajouter ici

        // Scope global pour isDeleted (si ce champ existe dans ta table)
        static::addGlobalScope('isDeleted', function (Builder $builder) {
            $builder->where('isDeleted', '=', 0);
        });
    }
}
