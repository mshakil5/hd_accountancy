<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClientTestimonial;
use Illuminate\Support\Facades\Auth;

class TestimonialController extends Controller
{
    public function index()
    {
        $data = ClientTestimonial::orderBy('id', 'DESC')->get();
        return view('admin.client_testimonial.index', compact('data'));
    }

    public function store(Request $request)
    {
        if(empty($request->title)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \" Short Title \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        
        $data = new ClientTestimonial;
        $data->link = $request->link;
        $data->title = $request->title;
        $data->description = $request->description;
        $data->created_by =  Auth::id();

        if ($request->hasFile('thumbnail')) {
            $thumbnail = $request->file('thumbnail');
            $thumbnailName = rand(10000000, 99999999) . '.' . $thumbnail->getClientOriginalExtension();
            $thumbnail->move(public_path('images/testimonial_thumbnails'), $thumbnailName);
            $data->thumbnail = '/images/testimonial_thumbnails/' . $thumbnailName;
        }

        if ($request->hasFile('video')) {
            $video = $request->file('video');
            $videoName = rand(10000000, 99999999) . '.' . $video->getClientOriginalExtension();
            $video->move(public_path('images/testimonial_videos'), $videoName);
            $data->video = '/images/testimonial_videos/' . $videoName;
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
        $info = ClientTestimonial::where($where)->get()->first();
        return response()->json($info);
    }

    public function update(Request $request)
    {
        $data = ClientTestimonial::find($request->codeid);

        if(empty($request->title)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \" Short Title \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }

        $data->link = $request->link;
        $data->title = $request->title;
        $data->description = $request->description;
        $data->updated_by =  Auth::id();

        if ($request->hasFile('thumbnail')) {
            if ($data->thumbnail && file_exists(public_path($data->thumbnail))) {
                unlink(public_path($data->thumbnail));
            }
            $thumbnail = $request->file('thumbnail');
            $thumbnailName = rand(10000000, 99999999) . '.' . $thumbnail->getClientOriginalExtension();
            $thumbnail->move(public_path('images/testimonial_thumbnails'), $thumbnailName);
            $data->thumbnail = '/images/testimonial_thumbnails/' . $thumbnailName;
        } else {
            $data->thumbnail = $data->thumbnail;
        }

        if ($request->hasFile('video')) {
            if ($data->video && file_exists(public_path($data->video))) {
                unlink(public_path($data->video));
            }
            $video = $request->file('video');
            $videoName = rand(10000000, 99999999) . '.' . $video->getClientOriginalExtension();
            $video->move(public_path('images/testimonial_videos'), $videoName);
            $data->video = '/images/testimonial_videos/' . $videoName;
        } else {
            $data->video = $data->video;
        }

        if ($data->save()) {
            $message ="<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Updated successfully.</b></div>";
            return response()->json(['status'=> 300,'message'=>$message]);
        }else{
            return response()->json(['status'=> 303,'message'=>'Server Error!!']);
        }
    }

    public function delete($id)
    {
        $data = ClientTestimonial::find($id);
        if (!$data) {
            return response()->json(['status' => 404, 'message' => 'Record not found!']);
        }

        $imagePath = public_path($data->image);

        if ($data->delete()) {
            if ($data->image && file_exists($imagePath)) {
                unlink($imagePath);
            }

            if ($data->video && file_exists(public_path($data->video))) {
                unlink(public_path($data->video));
            }

            $message = "<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Deleted successfully.</b></div>";
            return response()->json(['status' => 300, 'message' => $message]);
        } else {
            return response()->json(['status' => 303, 'message' => 'Server Error!!']);
        }
    }
}
