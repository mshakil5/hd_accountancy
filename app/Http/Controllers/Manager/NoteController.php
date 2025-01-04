<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Note;
use Illuminate\Support\Facades\Auth;
use DataTables;

class NoteController extends Controller
{
    public function getNotesByStaff(Request $request)

    {
        $notes = Note::where('user_id', Auth::id())->get();

        return DataTables::of($notes)
            ->addColumn('sl', function ($note) {
                return '';
            })
            ->addColumn('content', function ($note) {
                return $note->content;
            })
            ->addColumn('action', function ($note) {
                return '<button class="btn btn-primary">Action</button>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function saveNoteByStaff(Request $request)
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
}
