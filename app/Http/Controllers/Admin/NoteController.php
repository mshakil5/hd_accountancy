<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Note;
use Illuminate\Support\Facades\Auth;
use DataTables;
use Illuminate\Support\Facades\Validator;
use App\Models\Service;
use App\Models\ClientService;
use App\Models\RecentUpdate;

class NoteController extends Controller
{
    public function getNotes(Request $request)
    {
        // FIX 1: Remove ->get(). Pass the query builder so DataTables handles pagination (LIMIT/OFFSET).
        // FIX 2: Use select() to only get the columns you need.
        $notes = Note::where('user_id', Auth::id())
            ->select(['id', 'content', 'status']) 
            ->latest();

        return DataTables::of($notes)
            // We skip 'sl' here because you are calculating it in JS (faster)
            // We skip 'content' because it is automatically handled from the DB
            
            ->addColumn('action', function ($note) {
                // Note: Use e() or htmlspecialchars to prevent JS breakage if content has quotes
                return '<button class="btn btn-primary action-btn" data-note="' . e($note->content) . '" data-id="' . $note->id . '">Assign</button>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function saveNote(Request $request)
    {
        $request->validate([
            'note' => 'required|string|max:255',
        ]);

        $note = new Note();
        $note->user_id = Auth::id();
        $note->created_by = Auth::id();
        $note->content = $request->note;
        $note->save();

        return response()->json(['success' => true, 'message' => 'Note saved successfully.']);
    }

    public function assignNote(Request $request)
    {
        if ($request->type === 'one-time-job') {
            $validator = Validator::make($request->all(), [
                'note' => 'required|string',
                'manager_id' => 'required',
                'deadline' => 'nullable|date',
            ]);
            
            if ($validator->fails()) {
                return response()->json(['status' => 422, 'errors' => $validator->errors()->toArray()], 422);
            }
    
            $service = new Service();
            $service->name = $request->note;
            $service->status = 2;
            $service->created_by = auth()->id();
            $service->save();
    
            $clientService = new ClientService();
            $uniqueId = date("His") . '-' . mt_rand(1000, 9999);
            $clientService->service_id = $service->id;
            $clientService->manager_id = $request->manager_id;
            $clientService->legal_deadline = $request->deadline;
            $clientService->unique_id = $uniqueId;
            $clientService->type = 2;
            $clientService->save();
    
            $note = Note::find($request->note_id);
            $note->status = 2;
            $note->save();
    
            return response()->json(['status' => 200, 'message' => 'One-Time Job assigned successfully']);
            
        } elseif ($request->type === 'recent-update') {
            $validator = Validator::make($request->all(), [
                'note' => 'required|string',
                'client_id' => 'required|integer',
            ]);
    
            if ($validator->fails()) {
                return response()->json(['status' => 422, 'errors' => $validator->errors()->toArray()], 422);
            }
    
            $recentUpdate = new RecentUpdate();
            $recentUpdate->note = $request->note;
            $recentUpdate->client_id = $request->client_id;
            $recentUpdate->created_by = auth()->id();
            $recentUpdate->save();
    
            $note = Note::find($request->note_id);
            $note->status = 2;
            $note->save();
    
            return response()->json(['status' => 200, 'message' => 'Recent update added successfully']);
        }
    
        return response()->json(['status' => 400, 'message' => 'Invalid request type'], 400);
    }
}
