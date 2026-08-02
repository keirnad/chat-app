<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use App\Models\User;
use Symfony\Component\Mailer\Event\MessageEvent;

class ChatController extends Controller
{
    public function index(User $user) {
        return Message::query()
            -> where(function ($query) use ($user) {
                $query -> where('sender_id', auth()->id())
                    -> where ('receiver_id', $user->id);
            })
            -> orWhere(function ($query) use ($user) {
                $query->where('sender_id', $user->id)
                    ->where('receiver_id', auth()->id());
            })
            -> with(['sender', 'receiver'])
            -> orderBy('created_at', 'asc')
            ->get();
    }
    public function show(User $user) {
        return view('chat', ['user' => $user]);
    }

    public function send(Request $request, User $user) {
        $message = Message::create([
            'sender_id' => auth()->id(),
            'receiver_id' => $user->id,
            'text' => $request -> input('message')
        ]);
        broadcast(new MessageEvent($message));

        return response() -> json($message);
    }
}
