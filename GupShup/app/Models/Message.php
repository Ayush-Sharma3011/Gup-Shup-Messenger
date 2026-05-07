<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model as MongoModel;

class Message extends MongoModel
{
    protected $connection = 'mongodb';
    protected $collection = 'messages';

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'ciphertext',
        'iv',
        'type',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    /**
     * Get the conversation this message belongs to.
     */
    public function conversation()
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    /**
     * Get the sender of this message.
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
