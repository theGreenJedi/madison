<?php

namespace App\Events;

use App\Events\Event;
use App\Models\User;
use App\Models\Annotation;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class FeedbackSeen extends Event
{
    use SerializesModels;

    public $feedback;
    public $user;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Annotation $feedback, User $user)
    {
        $this->feedback = $feedback;
        $this->user = $user;
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }

    public static function getName()
    {
        return 'madison.feedback.seen';
    }

    public static function getDescription()
    {
        return 'When feedback (a note or comment) of yours is seen by a document sponsor';
    }

    public static function getType()
    {
        return static::TYPE_USER;
    }
}
