<?php

namespace App\Http\Controllers\Admin;

use App\Models\Contact;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Softcode;
use App\Models\Master;

class ContactMessageController extends Controller
{
   public function index()
   {
    $data = Contact::orderby('id','DESC')->get();
    return view('admin.contact_message.index', compact('data'));
   }

   public function webContact()
    {
        $softcode = Softcode::where('name', 'Contact')->first();
        $contactHeading = $softcode ? Master::where('softcode_id', $softcode->id)->first() : null;
        return view('admin.contact.web_contact', compact('contactHeading'));
    }

    public function webContactUpdate(Request $request)
    {
        $request->validate([
            'short_title' => 'required|string|max:255',
            'long_title' => 'required|string|max:255',
            'long_description' => 'required|string',
            'meta_image' => 'nullable|image|mimes:jpg,png,jpeg,gif|max:2048',
        ]);

        $softcode = Softcode::where('name', 'Contact')->first();
        if (!$softcode) {
            return redirect()->back()->withErrors(['error' => 'Softcode not found.']);
        }

        $contactHeading = Master::where('softcode_id', $softcode->id)->first();
        if (!$contactHeading) {
            return redirect()->back()->withErrors(['error' => 'Contact heading not found.']);
        }

        $contactHeading->short_title = $request->input('short_title');
        $contactHeading->long_title = $request->input('long_title');
        $contactHeading->long_description = $request->input('long_description');

         if ($request->hasFile('meta_image')) {
            if ($contactHeading->meta_image) {
               $oldImagePath = public_path('images/meta_image/' . $contactHeading->meta_image);
               if (file_exists($oldImagePath)) {
                     unlink($oldImagePath);
               }
            }

            $image = $request->file('meta_image');
            $imageName = rand(10000000, 99999999) . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/meta_image'), $imageName);
            $contactHeading->meta_image = $imageName;
         }

        $contactHeading->save();

        return redirect()->back()->with('success', 'Contact page updated successfully.');
    }
}
