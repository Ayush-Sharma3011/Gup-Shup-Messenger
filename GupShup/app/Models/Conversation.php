<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model as MongoModel;

class Conversation extends MongoModel
{
    protected $connection = 'mongodb';
    protected $collection = 'conversations';

    protected $fillable = [
        'participants',
        'last_message_text',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'participants' => 'array',
            'last_message_at' => 'datetime',
        ];
    }

    /**
     * Get the messages for this conversation.
     */
    public function messages()
    {
        return $this->hasMany(Message::class, 'conversation_id');
    }

    /**
     * Get the other participant (not the given user).
     */
    public function getOtherParticipant($userId)
    {
        $participants = $this->participants ?? [];
        $otherId = collect($participants)->first(fn($id) => (string) $id !== (string) $userId);
        return $otherId ? User::find($otherId) : null;
    }

    /**
     * Find an existing conversation between two users.
     */
    public static function findBetween($userId1, $userId2)
    {
        $allConversations = static::all();
        return $allConversations->first(function ($conv) use ($userId1, $userId2) {
            $participants = array_map('strval', $conv->participants ?? []);
            return in_array((string) $userId1, $participants) && in_array((string) $userId2, $participants);
        });
    }
}
