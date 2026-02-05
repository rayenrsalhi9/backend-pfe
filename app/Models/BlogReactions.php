<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uuids;
use Illuminate\Notifications\Notifiable;

class BlogReactions extends Model
{
    use HasFactory;
    use Notifiable, Uuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'blog_reactions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['blog_id','user_id','type'];

    public function user()
    {
        return $this->belongsTo(Users::class,'user_id','id');
    }

    public function blog()
    {
        return $this->belongsTo(Blogs::class,'blog_id','id');
    }
}
