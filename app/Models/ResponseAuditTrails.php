<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

use Illuminate\Notifications\Notifiable;
use App\Traits\Uuids;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Auth;

class ResponseAuditTrails extends Model
{
    use HasFactory;
    use Notifiable, Uuids;
    
    protected $table = 'response_audit_trails';
    protected $primaryKey = 'id';
    public $incrementing = false;
    
    const CREATED_AT = 'createdDate';
    const UPDATED_AT = 'modifiedDate';
    
    protected $fillable = [
        'forumId',
        'responseId', 
        'operationName',
        'responseContent',
        'responseType', // 'comment' or 'reaction'
        'previousContent',
        'ipAddress',
        'userAgent',
        'createdBy',
        'modifiedBy',
        'isDeleted'
    ];
    
    protected $casts = [
        'createdDate' => 'datetime',
        'modifiedDate' => 'datetime',
        'isDeleted' => 'boolean'
    ];
    
    public function forum()
    {
        return $this->belongsTo(Forums::class, 'forumId');
    }
    
    public function user()
    {
        return $this->belongsTo(Users::class, 'createdBy');
    }
    
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (Auth::check()) {
                $userId = Auth::id();
                $model->createdBy = $userId;
                $model->modifiedBy = $userId;
                $model->setAttribute($model->getKeyName(), Uuid::uuid4());
            }
        });
        
        static::updating(function ($model) {
            if (Auth::check()) {
                $userId = Auth::id();
                $model->modifiedBy = $userId;
            }
        });
        
        static::addGlobalScope('isDeleted', function (Builder $builder) {
            $builder->where('isDeleted', '=', false);
        });
    }
}