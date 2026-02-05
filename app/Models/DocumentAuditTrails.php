<?php

namespace App\Models;

use Ramsey\Uuid\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use App\Traits\Uuids;

class DocumentAuditTrails extends Model
{
    use HasFactory, SoftDeletes;
    use Notifiable, Uuids;
    protected $primaryKey = "id";
    protected  $table = 'documentAuditTrails';
    public $incrementing = false;

    const CREATED_AT = 'createdDate';
    const UPDATED_AT = 'modifiedDate';

    protected $fillable = [
        'documentId', 'operationName','assignToUserId','assignToRoleId', 'createdBy',
        'modifiedBy', 'isDeleted'
    ];

    public function user()
    {
        return $this->belongsTo(Users::class, 'createdBy','assignToUserId');
    }

    public function document()
    {
        return $this->belongsTo(Documents::class, 'documentId');
    }

    public function roles()
    {
        return $this->belongsTo(Roles::class, 'assignToRoleId');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Model $model) {
            $userId = Auth::parseToken()->getPayload()->get('userId');
            $model->createdBy= $userId;
            $model->modifiedBy =$userId;
            $model->setAttribute($model->getKeyName(), Uuid::uuid4());
        });
        static::updating(function (Model $model) {
            $userId = Auth::parseToken()->getPayload()->get('userId');
            $model->modifiedBy =$userId;
        });

    }
}
