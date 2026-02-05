<?php

namespace App\Models;

use Ramsey\Uuid\Uuid;
use Illuminate\Notifications\Notifiable;
use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReminderNotifications extends Model
{
    use HasFactory;
    use Notifiable, Uuids;
    protected $primaryKey = "id";
    protected $keyType = 'string';
    public $incrementing = false;
    public $table = 'reminderNotifications';
    public $timestamps = false;

    protected $fillable =  [
        'reminderId', 'subject', 'description',
        'fetchDateTime', 'isDeleted', 'isEmailNotification'
    ];

    public function reminders()
    {
        return $this->belongsTo(Reminders::class, 'reminderId', 'id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Model $model) {
            $model->setAttribute($model->getKeyName(), Uuid::uuid4());
        });

        static::addGlobalScope('isDeleted', function (Builder $builder) {
            $builder->where('reminders.isDeleted', '=', 0);
        });
    }
}
