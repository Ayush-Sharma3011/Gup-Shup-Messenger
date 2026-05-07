<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;



class ChatController extends Controller
{
    /**
     * Normalize participants array to ensure all IDs are strings.
     */
    private function normalizeParticipants($participants)
    {
        return array_map(fn($id) => (string) $id, $participants ?? []);
    }

    /**
     * Check if user is a participant (with proper type casting).
     */
    private function isParticipant($userId, $participants)
    {
        $normalized = $this->normalizeParticipants($participants);
        return in_array((string) $userId, $normalized, true);
    }

    /**
     * Show the main chat page.
     */
    
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        // Update online status
        $user->update(['is_online' => true, 'last_seen' => now()]);

        return view('chat.index', [
            'currentUser' => $user,
        ]);
    }

    /**
     * Get conversations for the current user (API).
     */
    public function getConversations()
    {
        $userId = (string) Auth::id();

        $allConversations = Conversation::orderBy('last_message_at', 'desc')->get();
        $conversations = $allConversations->filter(function ($conv) use ($userId) {
            return $this->isParticipant($userId, $conv->participants ?? []);
        })->values();

        $result = [];
        foreach ($conversations as $conversation) {
            $other = $conversation->getOtherParticipant($userId);
            if (!$other) continue;

            // Count unread messages
            $unreadCount = Message::where('conversation_id', (string) $conversation->_id)
                ->where('sender_id', '!=', $userId)
                ->whereNull('read_at')
                ->count();

            $result[] = [
                'id' => (string) $conversation->_id,
                'other_user' => [
                    'id' => (string) $other->_id,
                    'name' => $other->name,
                    'avatar_color' => $other->avatar_color ?? '#6C5CE7',
                    'is_online' => $other->is_online ?? false,
                    'public_key' => $other->public_key,
                ],
                'last_message_text' => $conversation->last_message_text,
                'last_message_at' => $conversation->last_message_at ? $conversation->last_message_at->toIso8601String() : null,
                'unread_count' => $unreadCount,
            ];
        }

        return response()->json($result);
    }

    /**
     * Get messages for a conversation (API).
     */
    public function getMessages($conversationId)
    {
        $userId = (string) Auth::id();
        $conversationId = (string) $conversationId;

        $conversation = Conversation::find($conversationId);
        if (!$conversation || !$this->isParticipant($userId, $conversation->participants ?? [])) {
            return response()->json(['error' => 'Not authorized'], 403);
        }

        $messages = Message::where('conversation_id', $conversationId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($msg) use ($userId) {
                $isMine = (string) $msg->sender_id === $userId;
                return [
                    'id' => (string) $msg->_id,
                    'sender_id' => (string) $msg->sender_id,
                    // MAGIC HAPPENS HERE: Serve the correct payload
                    'ciphertext' => $isMine ? $msg->sender_ciphertext : $msg->recipient_ciphertext,
                    'iv' => $isMine ? $msg->sender_iv : $msg->recipient_iv,
                    'type' => $msg->type ?? 'text',
                    'is_mine' => $isMine,
                    'read_at' => $msg->read_at ? $msg->read_at->toIso8601String() : null,
                    'created_at' => $msg->created_at->toIso8601String(),
                ];
            });

        return response()->json($messages);
    }

    /**
     * Send a message (API).
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'conversation_id' => ['required', 'string'],
            'recipient_ciphertext' => ['required', 'string'],
            'recipient_iv' => ['required', 'string'],
            'sender_ciphertext' => ['required', 'string'],
            'sender_iv' => ['required', 'string'],
        ]);

        $userId = (string) Auth::id();
        $conversationId = (string) $request->conversation_id;

        $conversation = Conversation::find($conversationId);
        if (!$conversation || !$this->isParticipant($userId, $conversation->participants ?? [])) {
            return response()->json(['error' => 'Not authorized'], 403);
        }

        $message = Message::create([
            'conversation_id' => $conversationId,
            'sender_id' => $userId,
            'recipient_ciphertext' => $request->recipient_ciphertext,
            'recipient_iv' => $request->recipient_iv,
            'sender_ciphertext' => $request->sender_ciphertext,
            'sender_iv' => $request->sender_iv,
            'type' => $request->type ?? 'text',
        ]);

        $conversation->update([
            'last_message_text' => 'Encrypted message', // Don't store ciphertext in convo data
            'last_message_at' => now(),
        ]);

        return response()->json([
            'id' => (string) $message->_id,
            'sender_id' => (string) $message->sender_id,
            'ciphertext' => $message->sender_ciphertext, // Send the sender's version back
            'iv' => $message->sender_iv,
            'type' => $message->type,
            'is_mine' => true,
            'read_at' => null,
            'created_at' => $message->created_at->toIso8601String(),
        ]);
    }

    /**
     * Mark messages in a conversation as read (API).
     */
    public function markAsRead($conversationId)
    {
        $userId = (string) Auth::id();
        $conversationId = (string) $conversationId;

        Message::where('conversation_id', $conversationId)
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * Search users (API).
     */
    public function searchUsers(Request $request)
    {
        $query = $request->get('q', '');
        $userId = (string) Auth::id();

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $users = User::where('_id', '!=', $userId)
            ->where(function ($q) use ($query) {
                $q->where('name', 'regex', '/' . preg_quote($query) . '/i')
                  ->orWhere('email', 'regex', '/' . preg_quote($query) . '/i');
            })
            ->limit(10)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => (string) $user->_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar_color' => $user->avatar_color ?? '#6C5CE7',
                    'is_online' => $user->is_online ?? false,
                    'public_key' => $user->public_key,
                ];
            });

        return response()->json($users);
    }

    /**
     * Create a new conversation or return existing one (API).
     */
    public function createConversation(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'string'],
        ]);

        $userId = (string) Auth::id();
        $otherUserId = (string) $request->user_id;

        $otherUser = User::find($otherUserId);
        if (!$otherUser) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Check if conversation already exists
        $existing = Conversation::findBetween($userId, $otherUserId);
        if ($existing) {
            return response()->json([
                'id' => (string) $existing->_id,
                'other_user' => [
                    'id' => (string) $otherUser->_id,
                    'name' => $otherUser->name,
                    'avatar_color' => $otherUser->avatar_color ?? '#6C5CE7',
                    'is_online' => $otherUser->is_online ?? false,
                    'public_key' => $otherUser->public_key,
                ],
                'last_message_text' => $existing->last_message_text,
                'last_message_at' => $existing->last_message_at ? $existing->last_message_at->toIso8601String() : null,
                'unread_count' => 0,
            ]);
        }

        // Create new conversation with string IDs
        $conversation = Conversation::create([
            'participants' => [(string) $userId, (string) $otherUserId],
            'last_message_text' => null,
            'last_message_at' => now(),
        ]);

        return response()->json([
            'id' => (string) $conversation->_id,
            'other_user' => [
                'id' => (string) $otherUser->_id,
                'name' => $otherUser->name,
                'avatar_color' => $otherUser->avatar_color ?? '#6C5CE7',
                'is_online' => $otherUser->is_online ?? false,
                'public_key' => $otherUser->public_key,
            ],
            'last_message_text' => null,
            'last_message_at' => $conversation->last_message_at->toIso8601String(),
            'unread_count' => 0,
        ]);
    }

    /**
     * Poll for new messages (API).
     */
    public function pollMessages(Request $request)
    {
        $userId = (string) Auth::id();
        $since = $request->get('since');

        // Update online status
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->update(['is_online' => true, 'last_seen' => now()]);

        $allConversations = Conversation::all();
        $userConversations = $allConversations->filter(function ($conv) use ($userId) {
            return $this->isParticipant($userId, $conv->participants ?? []);
        });

        if ($since) {
            $sinceDate = Carbon::parse($since);
            $conversationIds = $userConversations->pluck('_id')->map(fn($id) => (string) $id)->toArray();

            $newMessages = Message::whereIn('conversation_id', $conversationIds)
                ->where('created_at', '>', $sinceDate)
                ->where('sender_id', '!=', $userId)
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($msg) {
                    $sender = User::find($msg->sender_id);
                    return [
                        'id' => (string) $msg->_id,
                        'conversation_id' => (string) $msg->conversation_id,
                        'sender_id' => (string) $msg->sender_id,
                        'sender_public_key' => $sender ? $sender->public_key : null,
                        // Incoming polled messages are always from someone else, so we use recipient payload
                        'ciphertext' => $msg->recipient_ciphertext,
                        'iv' => $msg->recipient_iv,
                        'type' => $msg->type ?? 'text',
                        'is_mine' => false,
                        'read_at' => $msg->read_at ? $msg->read_at->toIso8601String() : null,
                        'created_at' => $msg->created_at->toIso8601String(),
                    ];
                });

            // Also return updated conversation list metadata
            $updatedConversations = [];
            foreach ($newMessages->groupBy('conversation_id') as $convId => $msgs) {
                $conv = Conversation::find((string) $convId);
                if ($conv) {
                    $other = $conv->getOtherParticipant($userId);
                    if ($other) {
                        $unreadCount = Message::where('conversation_id', (string) $convId)
                            ->where('sender_id', '!=', $userId)
                            ->whereNull('read_at')
                            ->count();

                        $updatedConversations[] = [
                            'id' => (string) $conv->_id,
                            'other_user' => [
                                'id' => (string) $other->_id,
                                'name' => $other->name,
                                'avatar_color' => $other->avatar_color ?? '#6C5CE7',
                                'is_online' => $other->is_online ?? false,
                                'public_key' => $other->public_key,
                            ],
                            'last_message_text' => $conv->last_message_text,
                            'last_message_at' => $conv->last_message_at ? $conv->last_message_at->toIso8601String() : null,
                            'unread_count' => $unreadCount,
                        ];
                    }
                }
            }

            return response()->json([
                'messages' => $newMessages->values(),
                'conversations' => $updatedConversations,
            ]);
        }

        return response()->json([
            'messages' => [],
            'conversations' => [],
        ]);
    }

    /**
     * Heartbeat to update online status (API).
     */
    public function heartbeat()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->update(['is_online' => true, 'last_seen' => now()]);
        return response()->json(['ok' => true]);
    }
}