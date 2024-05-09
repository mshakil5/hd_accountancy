<?php

namespace App\Http\Controllers\Admin;

use App\Models\HolidayType;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class HolidayTypeController extends Controller
{
    public function index()
    {
        $data = HolidayType::orderby('id','DESC')->get();
        return view('admin.holiday_type.index', compact('data'));
    }

    public function store(Request $request)
    {
        if(empty($request->type)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Type \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }

        $chkname = HolidayType::where('type',$request->type)->first();
        if($chkname){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>This type already added.</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        $data = new HolidayType;
        $data->type = $request->type;
        $data->day = $request->day;
        $data->created_by = Auth::id();
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
        $info = HolidayType::where($where)->get()->first();
        return response()->json($info);
    }

    public function update(Request $request)
    {
        if(empty($request->type)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"type \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }

        $duplicatename = HolidayType::where('type',$request->type)->where('id','!=', $request->codeid)->first();
        if($duplicatename){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>This type already added.</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }

        $data = HolidayType::find($request->codeid);
        $data->type = $request->type;
        $data->day = $request->day;
        $data->updated_by = Auth::id();
        if ($data->save()) {
            $message ="<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Data Updated Successfully.</b></div>";
            return response()->json(['status'=> 300,'message'=>$message]);
        }
        else{
            return response()->json(['status'=> 303,'message'=>'Server Error!!']);
        } 
    }

    public function delete($id)
    {
        if(HolidayType::destroy($id)){
            return response()->json(['success'=>true,'message'=>'Data has been deleted successfully']);
        }else{
            return response()->json(['success'=>false,'message'=>'Delete Failed']);
        }
    }
}
