<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model as MongoModel;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;

class User extends MongoModel implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword, MustVerifyEmail, Notifiable;

    protected $connection = 'mongodb';
    protected $collection = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar_color',
        'public_key',
        'is_online',
        'last_seen',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_online' => 'boolean',
            'last_seen' => 'datetime',
        ];
    }

    /**
     * Get all conversations this user is part of.
     */
    public function conversations()
    {
        return Conversation::whereIn('participants', [$this->_id])->orderBy('last_message_at', 'desc');
    }

    /**
     * Generate a random avatar color for the user.
     */
    public static function generateAvatarColor(): string
    {
        $colors = [
            '#6C5CE7', '#A29BFE', '#00CEC9', '#81ECEC',
            '#E17055', '#FAB1A0', '#FDCB6E', '#E84393',
            '#00B894', '#55EFC4', '#0984E3', '#74B9FF',
            '#D63031', '#FF7675', '#636E72', '#B2BEC3',
        ];
        return $colors[array_rand($colors)];
    }

    
}
