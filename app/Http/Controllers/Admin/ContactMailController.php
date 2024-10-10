<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ContactMail;

class ContactMailController extends Controller
{
    public function contactMail()
    {
        $contactMail = ContactMail::first(); 
        return view('admin.contact_mail.index', compact('contactMail'));
    }

    public function contactMailUpdate(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'status' => 'boolean',
        ]);

        $contactMail = ContactMail::findOrFail($request->id);
        $contactMail->update([
            'email' => $request->email,
            'status' => $request->status,
        ]);

        return redirect()->back()->with('success', 'Contact mail updated successfully.');
    }
}
