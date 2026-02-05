<?php

namespace App\Models;

use Ramsey\Uuid\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReminderSchedulers extends Model
{
    use HasFactory;
    const CREATED_AT = 'createdDate';
    public $timestamps = false;
    protected  $table = 'reminderSchedulers';

    protected $fillable = [
        'frequency', 'isActive', 'duration', 'documentId', 'isEmailNotification', 'isRead',
        'createdDate', 'userId', 'subject', 'message'
    ];

    protected $casts = [
        'isRead' => 'boolean',
        'duration' => 'date'
    ];

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
