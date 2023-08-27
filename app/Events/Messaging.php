<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class Messaging implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $id;
    public $content;
    public $content_type;
    public $user_id;
    public $gym_id;
    public $trainer_id;
    public $sender_type;
    public $receiver_type;
    public $created_at;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Message $message)
    {

        if($message->content_type == 'text')
        {
            $this->id = $message->id;
            $this->content = $message->content;
            $this->content_type = $message->content_type;
            $this->user_id = $message->user_id;
            $this->gym_id = $message->gym_id;
            $this->trainer_id = $message->trainer_id;
            $this->sender_type = $message->sender_type;
            $this->created_at = $message->created_at;
            $this->receiver_type = $message->receiver_type;
        }else
        {
            $message->content = asset('/assets/messages/' . $message->content );
            $this->id = $message->id;
            $this->content = $message->content;
            $this->content_type = $message->content_type;
            $this->user_id = $message->user_id;
            $this->gym_id = $message->gym_id;
            $this->trainer_id = $message->trainer_id;
            $this->sender_type = $message->sender_type;
            $this->created_at = $message->created_at;
            $this->receiver_type = $message->receiver_type;
        }
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        if($this->user_id && $this->gym_id)
        {
            return new PresenceChannel('user'.$this->user_id.'gym'.$this->gym_id);
        }elseif ($this->user_id && $this->trainer_id)
        {
            return new PresenceChannel('user'.$this->user_id.'trainer'.$this->trainer_id);
        }elseif ($this->gym_id && $this->trainer_id)
        {
            return new PresenceChannel($this->gym_id.$this->trainer_id);
        }
    }
}
