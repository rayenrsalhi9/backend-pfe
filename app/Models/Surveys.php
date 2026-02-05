<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uuids;
use Illuminate\Notifications\Notifiable;

class Surveys extends Model
{
    use HasFactory;
    use Notifiable, Uuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'surveys';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['title','type','privacy','created_by','start_date','end_date','blog','forum','closed'];

    public function creator()
    {
        return $this->belongsTo(Users::class,'created_by','id');
    }

    public function answers()
    {
        return $this->hasMany(SurveyAnswers::class, 'survey_id', 'id')->orderBy('created_at', 'asc');
    }
}
