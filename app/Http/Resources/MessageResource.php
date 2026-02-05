<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [

            "conversationId" => $this->conversation_id,
            "senderId" => $this->sender_id,
            "content" => $this->content,
            "documentId" => $this->document_id,
            "isRead" => $this->is_read,
            "type" => $this->type,
            "id" => $this->id,
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at,
            "sender" => $this->sender,
            "conversation" => $this->conversation,
            "document" => $this->document,
            "reactions" => $this->reactions
        ];
    }
}
