<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uuids;
use Illuminate\Notifications\Notifiable;

class SurveyAnswers extends Model
{
    use HasFactory;
    use Notifiable, Uuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'survey_answers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['survey_id','user_id','answer'];

    public function user()
    {
        return $this->belongsTo(Users::class,'user_id','id');
    }

    public function survey()
    {
        return $this->belongsTo(Surveys::class,'survey_id','id');
    }
}
