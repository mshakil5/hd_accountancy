<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MailContent;
use Illuminate\Support\Facades\Auth;

class MailContentController extends Controller
{
    public function index()
    {
        $data = MailContent::first();
        return view('admin.mail_content.index',compact('data'));
    }

    public function edit($id)
    {
        $mailContent = MailContent::findOrFail($id);
        return response()->json($mailContent);
    }

     public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $mailContent = MailContent::findOrFail($id);
        $mailContent->title = $request->title;
        $mailContent->content = $request->content;
        $mailContent->save();

        return response()->json([
            'status' => 300,
            'message' => 'Data Updated Successfully!',
        ]);
    }
}
