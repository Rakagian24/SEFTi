<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Events\MessageSent;

class MessageController extends Controller
{
    // Daftar percakapan user (list, search, sort, pagination)
    public function index(Request $request)
    {
        $user = Auth::user();
        $search = $request->input('search');
        $sort = $request->input('sort', 'latest');
        $query = Conversation::query()
            ->where(function($q) use ($user) {
                $q->where('user_one_id', $user->id)
                  ->orWhere('user_two_id', $user->id);
            });
        if ($search) {
            $query->whereHas('userOne', function($q) use ($search, $user) {
                if ($user->id !== $q->getModel()->id) {
                    $q->where('name', 'like', "%$search%");
                }
            })->orWhereHas('userTwo', function($q) use ($search, $user) {
                if ($user->id !== $q->getModel()->id) {
                    $q->where('name', 'like', "%$search%");
                }
            })
            ->orWhereHas('lastMessage', function($q) use ($search) {
                $q->where('message', 'like', "%$search%");
            });
        }
        if ($sort === 'latest') {
            $query->orderByDesc('updated_at');
        } elseif ($sort === 'oldest') {
            $query->orderBy('updated_at');
        } // bisa tambah sort lain sesuai kebutuhan
        $conversations = $query->with(['userOne', 'userTwo', 'lastMessage'])
            ->paginate(5);
        return response()->json($conversations);
    }

    // Menampilkan detail percakapan (isi chat)
    public function show($conversationId)
    {
        $user = Auth::user();
        $conversation = Conversation::where('id', $conversationId)
            ->where(function($q) use ($user) {
                $q->where('user_one_id', $user->id)
                  ->orWhere('user_two_id', $user->id);
            })
            ->firstOrFail();
        $messages = Message::where('conversation_id', $conversation->id)
            ->orderBy('created_at', 'asc')
            ->paginate(5);
        return response()->json([
            'conversation' => $conversation,
            'messages' => $messages,
        ]);
    }

    // Memulai percakapan baru (new chat)
    public function store(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'to_user_id' => 'required|exists:users,id|not_in:' . $user->id,
            'message' => 'nullable|string|max:250',
            'attachment' => 'nullable|file|max:5120', // 5MB
        ]);
        $toUserId = $request->input('to_user_id');
        // Cek sudah ada percakapan?
        $exists = Conversation::where(function($q) use ($user, $toUserId) {
            $q->where('user_one_id', $user->id)->where('user_two_id', $toUserId);
        })->orWhere(function($q) use ($user, $toUserId) {
            $q->where('user_one_id', $toUserId)->where('user_two_id', $user->id);
        })->exists();
        if ($exists) {
            return response()->json(['message' => 'Percakapan sudah ada.'], 422);
        }
        $conversation = Conversation::create([
            'user_one_id' => $user->id,
            'user_two_id' => $toUserId,
        ]);
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('chat-attachments', 'public');
        }
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'message' => $request->input('message'),
            'attachment' => $attachmentPath,
        ]);
        $conversation->last_message_id = $message->id;
        $conversation->save();
        event(new MessageSent($message));
        return response()->json(['conversation' => $conversation, 'message' => $message]);
    }

    // Mengirim pesan pada percakapan yang sudah ada
    public function sendMessage(Request $request, $conversationId)
    {
        $user = Auth::user();
        $request->validate([
            'message' => 'nullable|string|max:250',
            'attachment' => 'nullable|file|max:5120', // 5MB
        ]);
        $conversation = Conversation::where('id', $conversationId)
            ->where(function($q) use ($user) {
                $q->where('user_one_id', $user->id)
                  ->orWhere('user_two_id', $user->id);
            })
            ->firstOrFail();
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('chat-attachments', 'public');
        }
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'message' => $request->input('message'),
            'attachment' => $attachmentPath,
        ]);
        $conversation->last_message_id = $message->id;
        $conversation->save();
        event(new MessageSent($message));
        return response()->json(['message' => $message]);
    }

    // Edit pesan (hanya pesan sendiri)
    public function update(Request $request, $messageId)
    {
        $user = Auth::user();
        $request->validate([
            'message' => 'required|string|max:250',
        ]);
        $message = Message::where('id', $messageId)
            ->where('sender_id', $user->id)
            ->firstOrFail();
        $message->message = $request->input('message');
        $message->edited_at = now();
        $message->save();
        return response()->json(['message' => $message]);
    }

    // Hapus pesan (hanya pesan sendiri)
    public function destroy($messageId)
    {
        $user = Auth::user();
        $message = Message::where('id', $messageId)
            ->where('sender_id', $user->id)
            ->firstOrFail();
        $conversation = $message->conversation;
        $message->delete();
        // Update last_message_id jika perlu
        if ($conversation->last_message_id == $messageId) {
            $last = Message::where('conversation_id', $conversation->id)
                ->orderByDesc('id')
                ->first();
            $conversation->last_message_id = $last ? $last->id : null;
            $conversation->save();
        }
        return response()->json(['message' => 'Pesan berhasil dihapus.']);
    }

    // Hapus percakapan (opsional, jika diizinkan)
    public function deleteConversation($conversationId)
    {
        $user = Auth::user();
        $conversation = Conversation::where('id', $conversationId)
            ->where(function($q) use ($user) {
                $q->where('user_one_id', $user->id)
                  ->orWhere('user_two_id', $user->id);
            })
            ->firstOrFail();
        $conversation->messages()->delete();
        $conversation->delete();
        return response()->json(['message' => 'Percakapan berhasil dihapus.']);
    }

    // Daftar user yang bisa diajak chat (untuk dropdown New Chat)
    public function availableContacts(Request $request)
    {
        $user = Auth::user();
        $existing = Conversation::where('user_one_id', $user->id)
            ->orWhere('user_two_id', $user->id)
            ->pluck('user_one_id', 'user_two_id')
            ->flatten()
            ->unique()
            ->toArray();
        $contacts = User::where('id', '!=', $user->id)
            ->whereNotIn('id', $existing)
            ->get(['id', 'name']);
        return response()->json($contacts);
    }
}
