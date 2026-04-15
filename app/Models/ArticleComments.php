<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uuids;
use Illuminate\Notifications\Notifiable;

class ArticleComments extends Model
{
    use HasFactory;
    use Notifiable, Uuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'article_comments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['article_id', 'user_id', 'comment'];

    public function article()
    {
        return $this->belongsTo(Articles::class, 'article_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id', 'id');
    }
}
