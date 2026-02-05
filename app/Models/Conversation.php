<?php

namespace App\Models;

use App\Models\Users;
use App\Traits\Uuids;
use App\Models\ConversationMessage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;



class Conversation extends Model
{
    use HasFactory;
    use Notifiable, Uuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'conversations';

    protected $fillable = [
        'id',
        'title',
        'created_at',
        'updated_at',
    ];

    public function users()
    {
        return $this->belongsToMany(Users::class, 'conversation_users', 'conversation_id', 'user_id');
    }

    public function messages()
    {
        return $this->hasMany(ConversationMessage::class)->orderBy('created_at', 'asc');
    }

    public function lastMessage()
    {
        return $this->hasOne(ConversationMessage::class)->latest('created_at');
    }
}
