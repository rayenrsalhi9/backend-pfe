<?php

namespace App\Models;

use Ramsey\Uuid\Uuid;
use Illuminate\Notifications\Notifiable;
use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserNotifications extends Model
{
    use HasFactory;
    use Notifiable, Uuids;
    protected $primaryKey = "id";
    public $table = 'userNotifications';
    // public $timestamps = false;
    const CREATED_AT = 'createdDate';
    const UPDATED_AT = 'modifiedDate';

    protected $fillable = [
        'userId', 'message', 'isRead', 'documentId','type'
    ];

    // protected $dates = ['createdDate', 'modifiedDate'];

    protected $casts = [
        'isRead' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(Users::class, 'userId');
    }

    public function documents()
    {
        return $this->belongsTo(Documents::class, 'documentId');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Model $model) {
            $model->setAttribute($model->getKeyName(), Uuid::uuid4());
        });
    }
}
