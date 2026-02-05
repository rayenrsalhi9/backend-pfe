<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uuids;
use Illuminate\Notifications\Notifiable;

class Forums extends Model
{
    use HasFactory;
    use Notifiable, Uuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'forums';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['title', 'content', 'privacy', 'created_by', 'category_id', 'sub_category_id', 'closed'];

    public function category()
    {
        return $this->hasOne(ForumCategories::class, 'id', 'category_id');
    }

    public function subCategory()
    {
        return $this->hasOne(ForumSubCategories::class, 'id', 'category_id');
    }

    public function creator()
    {
        return $this->belongsTo(Users::class, 'created_by', 'id');
    }

    public function reactions()
    {
        return $this->hasMany(ForumReactions::class, 'forum_id', 'id')->orderBy('created_at', 'asc');
    }

    public function reactionsUp()
    {
        return $this->hasMany(ForumReactions::class, 'forum_id', 'id')->where('type','up')->orderBy('created_at', 'asc');
    }

    public function reactionsDown()
    {
        return $this->hasMany(ForumReactions::class, 'forum_id', 'id')->where('type','down')->orderBy('created_at', 'asc');
    }

    public function reactionsHeart()
    {
        return $this->hasMany(ForumReactions::class, 'forum_id', 'id')->where('type','heart')->orderBy('created_at', 'asc');
    }

    public function comments()
    {
        return $this->hasMany(ForumComments::class, 'forum_id', 'id')->orderBy('created_at', 'asc');
    }

    public function tags()
    {
        return $this->hasMany(Tags::class, 'forum_id', 'id')->orderBy('created_at', 'asc');
    }
}
