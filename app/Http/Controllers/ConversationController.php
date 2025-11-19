<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConversationController extends Controller
{
   /**
     * Get all conversations for the authenticated user.
     */
    public function index()
    {
        $user = Auth::user();

        // Get conversations, and for each conversation,
        // load the 'users' relationship (so we know who we are talking to)
        // and the 'latestMessage' relationship.
        $conversations = $user->conversations()
            ->with(['users' => function ($query) use ($user) {
                // Get the *other* user in the chat, not ourselves
                $query->where('users.id', '!=', $user->id);
            }, 
            'latestMessage'
            ]) 
            ->get();

        return response()->json($conversations);
    }

    /**
     * Get all messages for a specific conversation.
     */
    public function getMessages(string $conversationId)
    {
        $user = Auth::user();
        $conversation = $user->conversations()->findOrFail($conversationId);

        // Get all messages, and load the 'user' (sender) for each message
        $messages = $conversation->messages()
            ->with('user:id,name,profile_photo')
            ->orderBy('created_at', 'asc')
            ->get();

        // TODO: Mark messages as 'read'
        
        return response()->json($messages);
    }

    /**
     * Send a new message in a conversation.
     */
    public function sendMessage(Request $request, string $conversationId)
    {
        $user = Auth::user();
        $conversation = $user->conversations()->findOrFail($conversationId); // Ensures user is part of convo

        $validated = $request->validate([
            'body' => 'required|string|max:2000',
        ]);

        $message = $conversation->messages()->create([
            'user_id' => $user->id,
            'body' => $validated['body'],
        ]);

        $message->load('user:id,name,profile_photo'); // Load sender info to return to frontend

        return response()->json($message, 201);
    }

    /**
     * Start a new conversation (or get an existing one).
     * This is the function the employer will use.
     */
    public function startConversation(Request $request)
    {
        $user = Auth::user(); // This is the employer
        
        $validated = $request->validate([
            'recipient_id' => 'required|integer|exists:users,id',
        ]);

        $recipientId = $validated['recipient_id'];

        if ($recipientId == $user->id) {
            return response()->json(['message' => 'You cannot start a conversation with yourself.'], 422);
        }

        // Check if a conversation already exists between these two users
        // This is a complex query that looks for a conversation
        // that has *both* the current user ID and the recipient ID.
        $conversation = $user->conversations()
            ->whereHas('users', function ($query) use ($recipientId) {
                $query->where('user_id', $recipientId);
            })
            ->first();

        // If no conversation exists, create one
        if (!$conversation) {
            $conversation = Conversation::create();
            // Attach both users to the new conversation
            $conversation->users()->attach([$user->id, $recipientId]);
        }

        // Load the users (the recipient) and return the conversation
        $conversation->load(['users' => function ($query) use ($user) {
            $query->where('users.id', '!=', $user->id);
        }]);

        return response()->json($conversation, 201);
    }
}