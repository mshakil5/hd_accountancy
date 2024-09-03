<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PackageFeature;

class PackageFeatureController extends Controller
{
    public function index()
    {
        $data = PackageFeature::orderBy('id', 'DESC')->get();
        return view('admin.package_feature.index', compact('data'));
    }

    public function store(Request $request)
    {
        if(empty($request->name)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \" Package Feature Name \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        
        $existingFeature = PackageFeature::where('name', $request->name)->first();
        
        if ($existingFeature) {
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Package Feature with the same name already exists.</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
        } else {
            $data = new PackageFeature;
            $data->name = $request->name;
            if ($data->save()) {
                $message ="<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Created successfully.</b></div>";
                return response()->json(['status'=> 300,'message'=>$message]);
            }else{
                return response()->json(['status'=> 303,'message'=>'Server Error!!']);
            }
        }
    }

    public function edit($id)
    {
        $where = [
            'id'=>$id
        ];
        $info = PackageFeature::where($where)->get()->first();
        return response()->json($info);
    }

    public function update(Request $request)
    {
        if(empty($request->name)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \" Package Feature Name \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }

        $data = PackageFeature::find($request->codeid);

        $existingFeature = PackageFeature::where('name', $request->name)
                                        ->where('id', '!=', $request->codeid)
                                        ->first();
        
        if ($existingFeature) {
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Package Feature with the same name already exists.</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
        } else {
            $data->name = $request->name;
            
            if ($data->save()) {
                $message ="<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Updated Successfully.</b></div>";
                return response()->json(['status'=> 300,'message'=>$message]);
            }
            else{
                return response()->json(['status'=> 303,'message'=>'Server Error!!']);
            } 
        }
    }

    public function delete($id)
    {
        if(PackageFeature::destroy($id)){
            return response()->json(['success'=>true,'message'=>'Deleted successfully']);
        }else{
            return response()->json(['success'=>false,'message'=>'Delete Failed']);
        }
    } 
}
