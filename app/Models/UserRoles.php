<?php

namespace App\Models;

use Ramsey\Uuid\Uuid;
use App\Models\Roles;
use App\Models\Users;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use App\Traits\Uuids;

class UserRoles extends Model
{
    use HasFactory;
    use Notifiable, Uuids;
    protected $primaryKey = "id";
    protected $keyType = 'string';
    public $incrementing = false;
    public $table = 'userRoles';
    public $timestamps = false;

    protected $fillable = ['userId','roleId'];

    public function user()
    {
        return $this->belongsTo(Users::class, 'userId');
    }

    public function role()
    {
        return $this->belongsTo(Roles::class, 'roleId');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Model $model) {
            $model->setAttribute($model->getKeyName(), Uuid::uuid4());
        });
    }
}
