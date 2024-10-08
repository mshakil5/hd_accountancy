<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Master;
use App\Models\Softcode;
use App\Models\Career;

class CareerController extends Controller
{

    public function index()
    {
        $data = Career::orderBy('id', 'DESC')->get();
        return view('admin.career.index', compact('data'));
    }

    public function delete($id)
    {
        $data = Career::find($id);
        if (!$data) {
            return response()->json(['status' => 404, 'message' => 'Record not found!']);
        }

        $cvFilePath = public_path('images/Cv/' . $data->cv);
        if (file_exists($cvFilePath)) {
            unlink($cvFilePath);
        }

        if ($data->delete()) {
            return response()->json(['status' => 300, 'message' => 'Deleted successfully.']);
        } else {
            return response()->json(['status' => 303, 'message' => 'Server Error!!']);
        }
    }

    public function careerPage()
    {
        $getQuotationCode = Softcode::where('name', 'Career Page')->first();
        if ($getQuotationCode) {
            $career = Master::where('softcode_id', $getQuotationCode->id)->first();
        } else {
            $career = null;
        }

        return view('admin.career.career_page', compact('career'));
    }

    public function careerPageUpdate(Request $request)
    {
        $request->validate([
            'short_title' => 'required|string|max:255',
            'long_title' => 'required|string|max:255',
            'long_description' => 'required|string',
            'meta_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $softcode = Softcode::where('name', 'Career Page')->first();
        if (!$softcode) {
            return redirect()->back()->withErrors(['error' => 'Softcode not found.']);
        }

        $career = Master::where('softcode_id', $softcode->id)->first();
        if (!$career) {
            return redirect()->back()->withErrors(['error' => 'Not found.']);
        }

        $career->short_title = $request->input('short_title');
        $career->long_title = $request->input('long_title');
        $career->long_description = $request->input('long_description');

        if ($request->hasFile('meta_image')) {
            if ($career->meta_image) {
               $oldImagePath = public_path('images/meta_image/' . $career->meta_image);
               if (file_exists($oldImagePath)) {
                     unlink($oldImagePath);
               }
            }

            $image = $request->file('meta_image');
            $imageName = rand(10000000, 99999999) . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/meta_image'), $imageName);
            $career->meta_image = $imageName;
         }

        $career->save();

        return redirect()->back()->with('success', 'Updated successfully.');
    }
}
