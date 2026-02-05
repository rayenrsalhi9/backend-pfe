<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uuids;
use Illuminate\Notifications\Notifiable;

class Blogs extends Model
{
    use HasFactory;
    use Notifiable, Uuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'blogs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['title', 'subtitle', 'body', 'privacy', 'created_by', 'banner', 'expiration', 'category_id', 'picture', 'start_date', 'end_date'];

    public function category()
    {
        return $this->hasOne(BlogCategories::class, 'id', 'category_id');
    }

    public function creator()
    {
        return $this->belongsTo(Users::class, 'created_by', 'id');
    }

    public function reactions()
    {
        return $this->hasMany(BlogReactions::class, 'blog_id', 'id')->orderBy('created_at', 'asc');
    }

    public function reactionsUp()
    {
        return $this->hasMany(BlogReactions::class, 'blog_id', 'id')->where('type','up')->orderBy('created_at', 'asc');
    }

    public function reactionsDown()
    {
        return $this->hasMany(BlogReactions::class, 'blog_id', 'id')->where('type','down')->orderBy('created_at', 'asc');
    }

    public function comments()
    {
        return $this->hasMany(BlogComments::class, 'blog_id', 'id')->orderBy('created_at', 'asc');
    }

    public function tags()
    {
        return $this->hasMany(Tags::class, 'blog_id', 'id')->orderBy('created_at', 'asc');
    }
}
