<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;
use App\Models\Message;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = []; // We aren't mass-assigning anything for now

    /**
     * The users who are participating in this conversation.
     */
    public function users(): BelongsToMany
    {
        // Use the 'conversation_user' pivot table we created
        return $this->belongsToMany(User::class, 'conversation_user', 'conversation_id', 'user_id')
                    ->withTimestamps();
    }

    /**
     * The messages in this conversation.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'conversation_id')->latest();
    }

    /**
     * Get the latest message in the conversation.
     */
    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }
}