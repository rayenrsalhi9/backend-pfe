<?php

namespace App\Models;

use Ramsey\Uuid\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleClaims extends Model
{
    use HasFactory;
    protected $primaryKey = "id";
    protected $keyType = 'string';
    public $incrementing = false;

    public $table = 'roleClaims';
    public $timestamps = false;

    protected $fillable = ['roleId','actionId','claimType'];

    public function action()
    {
        return $this->belongsTo(Actions::class, 'actionId');
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
