<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserChatEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    private $data;
    private $uid;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($data, $uid)
    {
        $this->data = $data;
        $this->uid = $uid;

    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('user.'.$this->uid);
    }

    public function broadcastAs()
    {
        return 'chat-update';
    }

    public function broadcastWith()
    {
        return ['data'=>$this->data];
    }
}
