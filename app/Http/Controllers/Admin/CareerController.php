<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Master;
use App\Models\Softcode;

class CareerController extends Controller
{
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
        $career->save();

        return redirect()->back()->with('success', 'Updated successfully.');
    }
}
