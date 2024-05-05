<?php

namespace App\Http\Controllers\Admin;

use App\Models\LogComment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class LogController extends Controller
{
    public function addComment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'comment' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $logComment = new LogComment();
        $logComment->user_id = $request->user_id;
        $logComment->comment = $request->comment;
        $date = Carbon::today()->format('d-m-Y');
        $logComment->comment_date = $date;
        $logComment->save();

        return response()->json(['message' => 'Comment added successfully'], 200);
    }
}
