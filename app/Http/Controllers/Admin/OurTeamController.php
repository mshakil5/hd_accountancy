<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OurTeam;
use Illuminate\Support\Facades\Auth;

class OurTeamController extends Controller
{
    public function index()
    {
        $data = OurTeam::orderBy('id', 'DESC')->get();
        return view('admin.our_team.index', compact('data'));
    }

    public function store(Request $request)
    {
        if(empty($request->title)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Title \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        if(empty($request->name)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \" Name \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        if(empty($request->position)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \" Position \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        $nameExists = OurTeam::where('name', $request->name)->exists();
        if($nameExists){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Name already exists. Please choose a different name.</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        
        $data = new OurTeam;
        $data->title = $request->title;
        $data->name = $request->name;
        $data->position = $request->position;
        $data->short_description = $request->short_description;
        $data->created_by =  Auth::id();

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = rand(10000000, 99999999) . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/our_teams'), $imageName);
            $data->image = '/images/our_teams/' . $imageName;
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
        $info = OurTeam::where($where)->get()->first();
        return response()->json($info);
    }

    public function update(Request $request)
    {
        $data = OurTeam::find($request->codeid);
        if (!$data) {
            $message = "<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Not found..!</b></div>";
            return response()->json(['status' => 303, 'message' => $message]);
        }

        if(empty($request->title)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Title \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        if(empty($request->name)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \" Name \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        if(empty($request->position)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \" Position \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }

        $data->title = $request->title;
        $data->name = $request->name;
        $data->position = $request->position;
        $data->short_description = $request->short_description;
        $data->updated_by =  Auth::id();

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = rand(10000000, 99999999) . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/our_teams'), $imageName);
            $imagePath = '/images/our_teams/' . $imageName;

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
        $data = OurTeam::find($id);
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
