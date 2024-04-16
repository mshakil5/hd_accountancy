<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\WeWorkWithImage;
use App\Http\Controllers\Controller;

class WeWorkWithImageController extends Controller
{
    public function index()
    {   
        $data = WeWorkWithImage::orderby('id','DESC')->get();
        return view('admin.we_work_with_image.index', compact('data'));
    }
    public function store(Request $request)
    {
        $data =  new WeWorkWithImage;

        if($request->image != 'null'){
            $request->validate([
                'image' => 'required|mimes:jpeg,png,jpg,gif,svg,pdf|max:8048',
            ]);
            $rand = mt_rand(100000, 999999);
            $imageName = time(). $rand .'.'.$request->image->extension();
            $request->image->move(public_path('images/we_work_with_images'), $imageName);
            $data->image = $imageName;
        }

        if ($data->save()) {
            $message ="<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Data Created Successfully.</b></div>";
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
        $info = WeWorkWithImage::where($where)->get()->first();
        return response()->json($info);
    }

     public function update(Request $request)
     {
        $data = WeWorkWithImage::find($request->codeid);

        if ($request->image != 'null') {
            $request->validate([
                'image' => 'required|mimes:jpeg,png,jpg,gif,svg,pdf|max:8048',
            ]);

            if ($data->image && file_exists(public_path('images/we_work_with_images/' . $data->image))) {
                unlink(public_path('images/we_work_with_images/' . $data->image));
            }

            $rand = mt_rand(100000, 999999);
            $imageName = time(). $rand .'.'.$request->image->extension();
            $request->image->move(public_path('images/we_work_with_images/'), $imageName);
            $data->image = $imageName;
        }

        if ($data->save()) {
            $message = "<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Data Updated Successfully.</b></div>";
            return response()->json(['status'=> 300,'message'=>$message]);
        } else {
            return response()->json(['status'=> 303,'message'=>'Server Error!!']);
        } 

     }

     public function delete($id)
     {
        $data = WeWorkWithImage::find($id);

        if ($data->image && file_exists(public_path('images/we_work_with_images/' . $data->image))) {
            unlink(public_path('images/we_work_with_images/' . $data->image));
        }

        if ($data->delete()) {
            return response()->json(['success'=>true,'message'=>'Data has been deleted successfully']);
        } else {
            return response()->json(['success'=>false,'message'=>'Delete Failed']);
        }

     }

}
