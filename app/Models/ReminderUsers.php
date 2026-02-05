<?php

namespace App\Models;

use Ramsey\Uuid\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Users;
use Illuminate\Notifications\Notifiable;
use App\Traits\Uuids;

class ReminderUsers extends Model
{
    use HasFactory;
    use Notifiable, Uuids;
    protected $primaryKey = "id";
    protected $keyType = 'string';
    public $incrementing = false;

    public $table = 'reminderUsers';
    public $timestamps = false;

    protected $fillable = ['reminderId','userId'];

    public function user()
    {
        return $this->belongsTo(Users::class, 'userId');
    }

    public function reminder()
    {
        return $this->belongsTo(Reminders::class, 'reminderId');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Model $model) {
            $model->setAttribute($model->getKeyName(), Uuid::uuid4());
        });
    }
}
