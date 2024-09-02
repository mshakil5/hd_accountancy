<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Master;
use App\Models\Softcode;
use Illuminate\Support\Facades\Auth;

class MasterController extends Controller
{
    public function index()
    {
        $data = Master::orderBy('id', 'DESC')->get();
        $softCodes = Softcode::orderBy('id', 'DESC')->get();
        return view('admin.master.index', compact('data', 'softCodes'));
    }

    public function store(Request $request)
    {
        if(empty($request->softcode_id)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \" Soft Code \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
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
        
        $data = new Master;
        $data->name = $request->name;
        $data->softcode_id = $request->softcode_id;
        $data->short_title = $request->short_title;
        $data->long_title = $request->long_title;
        $data->short_description = $request->short_description;
        $data->long_description = $request->long_description;
        $data->meta_title = $request->meta_title;
        $data->meta_description = $request->meta_description;
        $data->created_by =  Auth::id();

        if ($request->hasFile('meta_image')) {
            $image = $request->file('meta_image');
            $imageName = rand(10000000, 99999999) . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/meta_image'), $imageName);
            $data->meta_image = $imageName;
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
        $info = Master::where($where)->get()->first();
        return response()->json($info);
    }

    public function update(Request $request)
    {
        $data = Master::find($request->codeid);

        if (!$data) {
            $message = "<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Record not found!</b></div>";
            return response()->json(['status' => 404, 'message' => $message]);
        }
        if (empty($request->softcode_id)) {
            $message = "<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Soft Code\" field..!</b></div>";
            return response()->json(['error' => $message], 422);
        }
        if (empty($request->short_title)) {
            $message = "<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Short Title\" field..!</b></div>";
            return response()->json(['status' => 303, 'message' => $message]);
        }
        if (empty($request->long_description)) {
            $message = "<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Long Description\" field..!</b></div>";
            return response()->json(['status' => 303, 'message' => $message]);
        }

        if ($request->hasFile('meta_image')) {
            if ($data->meta_image) {
                $oldImagePath = public_path('images/meta_image/' . $data->meta_image);
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            $image = $request->file('meta_image');
            $imageName = rand(10000000, 99999999) . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/meta_image'), $imageName);
            $data->meta_image = $imageName;
        }

        $data->name = $request->name;
        $data->softcode_id = $request->softcode_id;
        $data->short_title = $request->short_title;
        $data->long_title = $request->long_title;
        $data->short_description = $request->short_description;
        $data->long_description = $request->long_description;
        $data->meta_title = $request->meta_title;
        $data->meta_description = $request->meta_description;
        $data->updated_by = Auth::id();

        if ($data->save()) {
            $message ="<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Updated successfully.</b></div>";
            return response()->json(['status'=> 300,'message'=>$message]);
        }else{
            return response()->json(['status'=> 303,'message'=>'Server Error!!']);
        }
    }

    public function delete($id)
    {
        $data = Master::find($id);
        if (!$data) {
            return response()->json(['status' => 404, 'message' => 'Record not found!']);
        }

        $imagePath = public_path('images/meta_image/' . $data->meta_image);

        if ($data->delete()) {
            if ($data->meta_image && file_exists($imagePath)) {
                unlink($imagePath);
            }
            $message = "<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Deleted successfully.</b></div>";
            return response()->json(['status' => 300, 'message' => $message]);
        } else {
            return response()->json(['status' => 303, 'message' => 'Server Error!!']);
        }
    }

}
