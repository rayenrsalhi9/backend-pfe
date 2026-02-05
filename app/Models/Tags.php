<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uuids;
use Illuminate\Notifications\Notifiable;

class Tags extends Model
{
    use HasFactory;
    use Notifiable, Uuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tags';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['blog_id','forum_id','metatag','created_by'];

    public function blog()
    {
        return $this->belongsTo(Users::class,'blog_id','id');
    }

    public function forum()
    {
        return $this->belongsTo(Users::class,'forum_id','id');
    }

    public function creator()
    {
        return $this->belongsTo(Users::class,'created_by','id');
    }
}
