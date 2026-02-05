<?php

namespace App\Models;

use Ramsey\Uuid\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use App\Traits\Uuids;

class Actions extends Model
{
    use HasFactory, SoftDeletes;
    use Notifiable, Uuids;
    protected $primaryKey = "id";

    const CREATED_AT = 'createdDate';
    const UPDATED_AT = 'modifiedDate';

    protected $fillable = [
        'name','order','pageId','createdBy','code',
        'modifiedBy', 'isDeleted'
    ];

    public function pages()
    {
        return $this->belongsTo(Pages::class);
    }

    public function roleClaims()
    {
        return $this->hasMany(RoleClaims::class, 'actionId', 'id');
    }

    public function userClaims()
    {
        return $this->hasMany(UserClaims::class, 'actionId', 'id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Model $model) {
            $model->setAttribute($model->getKeyName(), Uuid::uuid4());
        });
    }
}
