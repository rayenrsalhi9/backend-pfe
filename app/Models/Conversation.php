<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Conversation extends Model
{
    use HasFactory, Notifiable, Uuids;

    protected $table = 'conversations';

    protected $fillable = [
        'id',
        'title',
        'type',
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
        return $this->hasOne(ConversationMessage::class, 'conversation_id', 'id')
            ->orderBy('created_at', 'desc');
    }

    public function lastContentMessage()
    {
        return $this->hasOne(ConversationMessage::class, 'conversation_id', 'id')
            ->where('type', '!=', 'reaction')
            ->orderBy('created_at', 'desc');
    }
}
