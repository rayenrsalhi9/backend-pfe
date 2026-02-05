<?php

namespace App\Models;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Builder;

class DocumentComments extends Model
{
    use HasFactory;
    use Notifiable, Uuids;
    protected $primaryKey = "id";
    protected  $table = 'documentComments';
    public $incrementing = false;

    const CREATED_AT = 'createdDate';
    const UPDATED_AT = 'modifiedDate';

    protected $fillable = [
        'documentId', 'comment', 'createdBy',
        'modifiedBy', 'isDeleted'
    ];

    public function user()
    {
        return $this->belongsTo(Users::class, 'userId');
    }

    public function document()
    {
        return $this->belongsTo(Documents::class, 'documentId');
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

        static::addGlobalScope('isDeleted', function (Builder $builder) {
            $builder->where('documentComments.isDeleted', '=', 0);
        });
    }
}
