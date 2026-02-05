<?php

namespace App\Models;

use Ramsey\Uuid\Uuid;
use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentTokens extends Model
{
    use HasFactory,Uuids;
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = "id";
    protected  $table = 'documentTokens';

    protected $fillable = [
        'documentId', 'token','createdDate'
    ];

    protected $dates = ['createdDate'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Model $model) {
            $model->setAttribute($model->getKeyName(), Uuid::uuid4());
        });
        
    }
}
