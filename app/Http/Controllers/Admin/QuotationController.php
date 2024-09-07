<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Quotation;
use App\Models\Softcode;
use App\Models\Master;

class QuotationController extends Controller
{
    public function index()
    {
        $data = Quotation::orderBy('id', 'DESC')->get();
        return view('admin.quotation.index', compact('data'));
    }

    public function delete($id)
    {
        $data = Quotation::find($id);
        if (!$data) {
            return response()->json(['status' => 404, 'message' => 'Record not found!']);
        }

        if ($data->delete()) {
            return response()->json(['status' => 300, 'message' => 'Deleted successfully.']);
        } else {
            return response()->json(['status' => 303, 'message' => 'Server Error!!']);
        }
    }

    public function quotationPage()
    {
        $getQuotationCode = Softcode::where('name', 'Get Quotation Page')->first();
        if ($getQuotationCode) {
            $getQuotation = Master::where('softcode_id', $getQuotationCode->id)->first();
        } else {
            $getQuotation = null;
        }

        return view('admin.quotation.quotation_page', compact('getQuotation'));
    }

    public function quotationPageUpdate(Request $request)
    {
        $request->validate([
            'short_title' => 'required|string|max:255',
            'long_title' => 'required|string|max:255',
            'long_description' => 'required|string',
        ]);

        $softcode = Softcode::where('name', 'Get Quotation Page')->first();
        if (!$softcode) {
            return redirect()->back()->withErrors(['error' => 'Softcode not found.']);
        }

        $quotion = Master::where('softcode_id', $softcode->id)->first();
        if (!$quotion) {
            return redirect()->back()->withErrors(['error' => 'Not found.']);
        }

        $quotion->short_title = $request->input('short_title');
        $quotion->long_title = $request->input('long_title');
        $quotion->long_description = $request->input('long_description');
        $quotion->save();

        return redirect()->back()->with('success', 'Updated successfully.');
    }
}
