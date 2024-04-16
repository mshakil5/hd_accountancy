<?php

namespace App\Http\Controllers\Admin;

use App\Models\WebService;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class WebServiceController extends Controller
{
    public function index()
    {   
        $data = WebService::orderby('id','DESC')->get();
        return view('admin.web_service.index', compact('data'));
    }

    public function store(Request $request)
    {
        if(empty($request->title)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Title \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        if(empty($request->meta_title)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Meta title \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }

        $data = new WebService;

        if ($request->hasFile('image')) {
            $rand = mt_rand(100000, 999999);
            $imageName = time(). $rand .'.'.$request->image->extension();
            $request->image->move(public_path('images/webservice'), $imageName);
            $data->image = $imageName;
        }

        $data->title = $request->title;
        $data->description = $request->description;
        $data->meta_title = $request->meta_title;
        $data->meta_description = $request->meta_description;
        $data->tag = $request->tag;
        $data->slug = Str::slug($request->title);

        $data->created_by = Auth::id();
        if ($data->save()) {
            $message = "<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Data Created Successfully.</b></div>";
            return response()->json(['status'=> 300,'message'=>$message]);
        } else {
            return response()->json(['status'=> 303,'message'=>'Server Error!!']);
        }
    }

    public function edit($id)
    {
        $where = [
            'id'=>$id
        ];
        $info = WebService::where($where)->get()->first();
        return response()->json($info);
    }

    public function update(Request $request)
    {

        if(empty($request->title)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Title \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        if(empty($request->meta_title)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Meta title \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }

        $data = WebService::find($request->codeid);
        if ($request->image != 'null') {
            $request->validate([
                'image' => 'required|mimes:jpeg,png,jpg,gif,svg,pdf|max:8048',
            ]);

            if ($data->image && file_exists(public_path('images/webservice/' . $data->image))) {
                unlink(public_path('images/webservice/' . $data->image));
            }

            $rand = mt_rand(100000, 999999);
            $imageName = time(). $rand .'.'.$request->image->extension();
            $request->image->move(public_path('images/webservice/'), $imageName);
            $data->image = $imageName;
        }

        $data->title = $request->title;
        $data->description = $request->description;
        $data->meta_title = $request->meta_title;
        $data->meta_description = $request->meta_description;
        $data->tag = $request->tag;
        $data->slug = Str::slug($request->title);
        $data->updated_by = Auth::id();
        
        if ($data->save()) {
            $message = "<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Data Updated Successfully.</b></div>";
            return response()->json(['status'=> 300,'message'=>$message]);
        } else {
            return response()->json(['status'=> 303,'message'=>'Server Error!!']);
        } 
    }

    public function delete($id)
    {
        $data = WebService::find($id);

        if ($data->image && file_exists(public_path('images/webservice/' . $data->image))) {
            unlink(public_path('images/webservice/' . $data->image));
        }

        if ($data->delete()) {
            return response()->json(['success'=>true,'message'=>'Data has been deleted successfully']);
        } else {
            return response()->json(['success'=>false,'message'=>'Delete Failed']);
        }
    }

}
