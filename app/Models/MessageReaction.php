<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uuids;
use Illuminate\Notifications\Notifiable;

class MessageReaction extends Model
{
     use HasFactory;

    use Notifiable, Uuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'message_reactions';

    protected $fillable = [
        'id',
        'conversation_message_id',
        'sender_id',
        'document_id',
        'type',
        'created_at',
        'updated_at',
    ];

    public function message()
    {
        return $this->belongsTo(ConversationMessage::class, 'conversation_message_id', 'id');
    }

    public function sender()
    {
        return $this->belongsTo(Users::class, 'sender_id', 'id');
    }
}
