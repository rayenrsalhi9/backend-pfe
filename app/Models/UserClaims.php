<?php

namespace App\Models;

use Ramsey\Uuid\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserClaims extends Model
{
    use HasFactory;
    protected $primaryKey = "id";
    protected $keyType = 'string';
    public $incrementing = false;
    public $table = 'userClaims';
    public $timestamps = false;

    protected $fillable = ['userId','actionId','claimType'];

    public function action()
    {
        return $this->belongsTo(Actions::class, 'actionId');
    }

    public function user()
    {
        return $this->belongsTo(Users::class, 'userId');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Model $model) {
            $model->setAttribute($model->getKeyName(), Uuid::uuid4());
        });
    }
}
