<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RecentUpdate;
use Illuminate\Support\Facades\Auth;

class RecentUpdateController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'note' => 'required|string',
            'client_id' => 'required|exists:clients,id',
        ]);

        $recentUpdate = new RecentUpdate();
        $recentUpdate->note = $request->note;
        $recentUpdate->client_id = $request->client_id;
        $recentUpdate->created_by = Auth::id();
        $recentUpdate->save();

        return response()->json(['message' => 'Recent update created successfully.'], 200);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'note' => 'required|string',
        ]);

        $recentUpdate = RecentUpdate::findOrFail($id);
        $recentUpdate->note = $request->note;
        $recentUpdate->save();

        return response()->json(['message' => 'Recent update updated successfully.'], 200);
    }

    public function destroy($id)
    {
        $recentUpdate = RecentUpdate::findOrFail($id);
        $recentUpdate->delete();

        return response()->json(['message' => 'Recent update deleted successfully.'], 200);
    }
}
