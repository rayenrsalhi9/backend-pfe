<?php

namespace App\Models;

use Ramsey\Uuid\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use App\Traits\Uuids;
use App\Models\Conversation;
use App\Models\Users;
use App\Models\Documents;

class ConversationMessage extends Model
{
    use HasFactory;

    use Notifiable, Uuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'conversation_messages';

    protected $fillable = [
        'id',
        'conversation_id',
        'sender_id',
        'document_id',
        'content',
        'type',
        'is_read',
        'created_at',
        'updated_at',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(Users::class, 'sender_id', 'id');
    }

    public function reactions()
    {
        return $this->hasMany(MessageReaction::class);
    }

    public function document()
    {
        return $this->belongsTo(Documents::class, 'document_id', 'id');
    }
}
