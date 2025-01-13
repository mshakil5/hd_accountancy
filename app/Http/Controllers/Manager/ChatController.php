<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ServiceMessage;

class ChatController extends Controller
{
    public function getMessagesByManager(Request $request)
    {
        ServiceMessage::where('staff_id', Auth::id())
            ->where('status', 1)
            ->whereNull('client_sub_service_id')
            ->whereNull('client_service_id')
            ->update(['status' => 0]);
    
        $query = ServiceMessage::where('staff_id', Auth::id())
            ->whereNull('client_sub_service_id')
            ->whereNull('client_service_id')
            ->select('id', 'message', 'created_at', 'created_by')
            ->with('user:id,first_name,last_name');
    
        if ($request->filter === 'today') {
            $query->whereDate('created_at', today());
        } elseif ($request->filter === 'last7days') {
            $query->where('created_at', '>=', now()->subDays(7));
        } elseif ($request->filter === 'last30days') {
            $query->where('created_at', '>=', now()->subDays(30));
        }
    
        $messages = $query->latest()->get();
    
        return response()->json($messages);
    }    

    public function sendMessageByManager(Request $request)
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

    public function getUnreadMessagesByManager()
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

    public function getMessagesByStaff(Request $request)
    {
        ServiceMessage::where('staff_id', Auth::id())
            ->where('status', 1)
            ->whereNull('client_sub_service_id')
            ->whereNull('client_service_id')
            ->update(['status' => 0]);
    
        $query = ServiceMessage::where('staff_id', Auth::id())
            ->whereNull('client_sub_service_id')
            ->whereNull('client_service_id')
            ->select('id', 'message', 'created_at', 'created_by')
            ->with('user:id,first_name,last_name');
    
        if ($request->filter === 'today') {
            $query->whereDate('created_at', today());
        } elseif ($request->filter === 'last7days') {
            $query->where('created_at', '>=', now()->subDays(7));
        } elseif ($request->filter === 'last30days') {
            $query->where('created_at', '>=', now()->subDays(30));
        }
    
        $messages = $query->latest()->get();
    
        return response()->json($messages);
    }    

    public function sendMessageByStaff(Request $request)
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

    public function getUnreadMessagesByStaff()
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
