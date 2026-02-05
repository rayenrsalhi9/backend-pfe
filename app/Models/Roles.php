<?php

namespace App\Models;

use Ramsey\Uuid\Uuid;
use App\Models\UserRoles;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User;

class Roles extends Model
{
    use HasFactory, SoftDeletes;
    use Notifiable, Uuids;
    protected $primaryKey = "id";

    const CREATED_AT = 'createdDate';
    const UPDATED_AT = 'modifiedDate';

    protected $fillable = [
        'name',
        'createdBy',
        'modifiedBy',
        'isDeleted'
    ];

    public function createdBy()
    {
        return $this->hasOne(User::class, 'id', 'createdBy');
    }

    public function modifiedBy()
    {
        return $this->hasOne(User::class, 'id', 'modifiedBy');
    }

    public function userRoles()
    {
        return $this->hasMany(UserRoles::class, 'roleId', 'id');
    }

    public function roleClaims()
    {
        return $this->hasMany(RoleClaims::class, 'roleId', 'id');
    }

    public function documentRolePermissions()
    {
        return $this->hasMany(DocumentRolePermissions::class, 'roleId', 'id');
    }

    public function documentAuditTrails()
    {
        return $this->hasMany(DocumentAuditTrails::class, 'assignToRoleId', 'id');
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
            $builder->where('isDeleted', '=', 0);
        });
    }
}
