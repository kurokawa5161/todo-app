<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Todo;
use Laravel\Reverb\Protocols\Pusher\Channels\PrivateChannel as ChannelsPrivateChannel;

class TodoUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Update a new event instance.
     */
    public function __construct(public Todo $todo) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('user.' . $this->todo->user_id)];

        //チームTodoの場合、チームチャンネルにも配信
        if ($this->todo->team_id) {
            $channels[] = new ChannelsPrivateChannel('team.' . $this->todo->team_id);
        }

        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'todo' => $this->todo->load(['category', 'tags']),
        ];
    }
}
