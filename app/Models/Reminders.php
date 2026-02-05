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

class Reminders extends Model
{
    use HasFactory, SoftDeletes;
    use Notifiable, Uuids;
    protected $primaryKey = "id";

    const CREATED_AT = 'createdDate';
    const UPDATED_AT = 'modifiedDate';

    protected $dates = ['startDate', 'endDate'];

    protected $fillable = [
        'subject', 'message', 'frequency', 'startDate', 'endDate', 'dayOfWeek',
        'isRepeated', 'isEmailNotification', 'documentId', 'createdBy',
        'modifiedBy', 'isDeleted'
    ];

    public function documents()
    {
        return $this->belongsTo(Documents::class, 'documentId');
    }

    public function reminderUsers()
    {
        return $this->hasMany(ReminderUsers::class, 'reminderId', 'id');
    }

    public function dailyReminders()
    {
        return $this->hasMany(DailyReminders::class,'reminderId');
    }

    public function remindernotifications()
    {
        return $this->hasMany(ReminderNotifications::class,'reminderId');
    }

    public function halfYearlyReminders()
    {
        return $this->hasMany(HalfYearlyReminders::class,'reminderId');
    }
    public function quarterlyReminders()
    {
        return $this->hasMany(QuarterlyReminders::class,'reminderId');
    }


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
            $builder->where('reminders.isDeleted', '=', 0);
        });
    }
}
