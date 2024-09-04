<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CaseStudy;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class CaseStudiesController extends Controller
{
    public function index()
    {
        $data = CaseStudy::orderBy('id', 'DESC')->get();
        return view('admin.case_studies.index', compact('data'));
    }

    public function store(Request $request)
    {
        if(empty($request->short_title)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \" Short Title \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        if(empty($request->long_description)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \" Long Description \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        
        $data = new CaseStudy;
        $data->short_title = $request->short_title;
        $data->slug = Str::slug($request->short_title);
        $data->long_title = $request->long_title;
        $data->short_description = $request->short_description;
        $data->long_description = $request->long_description;
        $data->created_by =  Auth::id();

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = rand(10000000, 99999999) . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/case_studies'), $imageName);
            $data->image = '/images/case_studies/' . $imageName;
        }

        if ($data->save()) {
            $message ="<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Created successfully.</b></div>";
            return response()->json(['status'=> 300,'message'=>$message]);
        }else{
            return response()->json(['status'=> 303,'message'=>'Server Error!!']);
        }
    }

    public function edit($id)
    {
        $where = [
            'id'=>$id
        ];
        $info = CaseStudy::where($where)->get()->first();
        return response()->json($info);
    }

    public function update(Request $request)
    {
        $data = CaseStudy::find($request->codeid);
        if (!$data) {
            $message = "<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Not found..!</b></div>";
            return response()->json(['status' => 303, 'message' => $message]);
        }

        if (empty($request->short_title)) {
            $message = "<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \" Short Title \" field..!</b></div>";
            return response()->json(['status' => 303, 'message' => $message]);
        }
        if (empty($request->long_description)) {
            $message = "<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \" Long Description \" field..!</b></div>";
            return response()->json(['status' => 303, 'message' => $message]);
        }

        $data->short_title = $request->short_title;
        $data->long_title = $request->long_title;
        $data->short_description = $request->short_description;
        $data->long_description = $request->long_description;
        $data->created_by = Auth::id();

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = rand(10000000, 99999999) . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/case_studies'), $imageName);
            $imagePath = '/images/case_studies/' . $imageName;

            if ($data->image && file_exists(public_path($data->image))) {
                unlink(public_path($data->image));
            }

            $data->image = $imagePath;
        }

        if ($data->save()) {
            $message = "<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Updated successfully.</b></div>";
            return response()->json(['status' => 300, 'message' => $message, 'data' => $data]);
        } else {
            return response()->json(['status' => 303, 'message' => 'Server Error!!']);
        }
    }

    public function delete($id)
    {
        $data = CaseStudy::find($id);
        if (!$data) {
            return response()->json(['status' => 404, 'message' => 'Record not found!']);
        }

        $imagePath = public_path($data->image);

        if ($data->delete()) {
            if ($data->image && file_exists($imagePath)) {
                unlink($imagePath);
            }
            $message = "<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Deleted successfully.</b></div>";
            return response()->json(['status' => 300, 'message' => $message]);
        } else {
            return response()->json(['status' => 303, 'message' => 'Server Error!!']);
        }
    }
}
