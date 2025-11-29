<?php

namespace App\Http\Controllers\Admin;

use App\Models\Contact;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Softcode;
use App\Models\Master;
use App\Models\ContactSubmission;

class ContactMessageController extends Controller
{
   public function index()
   {
    $data = Contact::orderby('id','DESC')->get();
    return view('admin.contact_message.index', compact('data'));
   }

    public function offerIndex()
    {
        $data = ContactSubmission::latest('id')->get();
        return view('admin.contact_message.offer_index', compact('data'));
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

    public function delete($id)
    {
        $data = Contact::find($id);
        if (!$data) {
            return response()->json(['status' => 404, 'message' => 'Record not found!']);
        }

        if ($data->delete()) {
            return response()->json(['status' => 300, 'message' => 'Deleted successfully.']);
        } else {
            return response()->json(['status' => 303, 'message' => 'Server Error!!']);
        }
    }

    public function deleteOffer($id)
    {
        try {
            $contact = ContactSubmission::find($id);
            if (!$contact) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Contact message not found.'
                ]);
            }

            $contact->delete();

            return response()->json([
                'status' => 200,
                'message' => 'Contact message deleted successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error deleting contact message.'
            ]);
        }
    }
}
