<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use PhpParser\Builder\Use_;

class Articles extends Model
{
    use HasFactory;
    use Notifiable, Uuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'articles';


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['title','short_text','long_text','privacy','created_by','article_category_id','picture'];


    public function category()
    {
        return $this->hasOne(ArticleCategories::class,'id','article_category_id');
    }

    public function creator()
    {
        return $this->belongsTo(Users::class,'created_by','id');
    }

    public function users()
    {
        return $this->hasMany(ArticleUsers::class, 'article_id','id');
    }

    public function images()
    {
        return $this->hasMany(ArticleImages::class);
    }
}
