<?php

namespace App\Models;


use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use App\Traits\Uuids;

class CompanyProfiles extends Model
{
    use HasFactory, SoftDeletes;
    use Notifiable, Uuids;

    protected $primaryKey = "id";
    protected  $table = 'companyProfile';

    const CREATED_AT = 'createdDate';
    const UPDATED_AT = 'modifiedDate';

    protected $fillable = [
        'title',  'logoUrl',
        'address','phoneNumber',
        'modifiedBy', 'isDeleted', 'bannerUrl',
        'email','linkedin','twitter','facebook','description'
    ];

    protected $hidden = ['createdBy', 'modifiedBy', 'deletedBy', 'createdDate', 'modifiedDate', 'isDeleted', 'deleted_at'];


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
    }
}
