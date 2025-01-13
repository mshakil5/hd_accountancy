<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ServiceMessage;

class ChatController extends Controller
{
    public function getMessages(Request $request)
    {
        ServiceMessage::where('staff_id', Auth::id())
            ->where('status', 1)
            ->whereNull('client_sub_service_id')
            ->whereNull('client_service_id')
            ->update(['status' => 0]);
    
        $messages = ServiceMessage::where('staff_id', Auth::id())
            ->whereNull('client_sub_service_id')
            ->whereNull('client_service_id')
            ->select('id', 'message', 'created_at', 'created_by')
            ->with('user:id,first_name,last_name')
            ->latest()
            ->get();
    
        return response()->json($messages);
    }    

    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'message' => 'required|string|max:1000',
        ]);

        ServiceMessage::create([
            'staff_id' => $request->recipient_id,
            'created_by' => Auth::id(),
            'message' => $validated['message'],
        ]);

        return response()->json(['message' => 'Message sent successfully!'], 200);
    }

    public function getUnreadMessages()
    {
        $unreadMessages = ServiceMessage::where('staff_id', Auth::id())
            ->whereNull('client_sub_service_id')
            ->whereNull('client_service_id')
            ->where('status', 1)
            ->count();

        return response()->json([
            'unread_count' => $unreadMessages
        ]);
    }
}
