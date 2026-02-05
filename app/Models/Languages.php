<?php

namespace App\Models;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Builder;

class Languages extends Model
{
    use HasFactory, SoftDeletes;
    use Notifiable, Uuids;
    protected $primaryKey = "id";
    public $table = 'languages';
    const CREATED_AT = 'createdDate';
    const UPDATED_AT = 'modifiedDate';

    protected $hidden = ['createdBy', 'modifiedBy', 'deletedBy', 'createdDate', 'modifiedDate', 'isDeleted', 'deleted_at'];

    protected $fillable = [
        'code', 'name', 'order', 'imageUrl', 'createdBy', 'modifiedBy', 'isDeleted'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Model $model) {
            $userId = Auth::parseToken()->getPayload()->get('userId');
            $model->createdBy = $userId;
            $model->modifiedBy = $userId;
            $model->setAttribute($model->getKeyName(), Uuid::uuid4());
        });

        static::updating(function (Model $model) {
            $userId = Auth::parseToken()->getPayload()->get('userId');
            $model->modifiedBy = $userId;
        });

        static::addGlobalScope('isDeleted', function (Builder $builder) {
            $builder->where('languages.isDeleted', '=', 0);
        });
    }
}
