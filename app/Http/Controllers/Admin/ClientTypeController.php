<?php

namespace App\Http\Controllers\Admin;

use App\Models\ClientType;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

class ClientTypeController extends Controller
{

    public function showClientTypeActivities($id)
    {
        $clientType = ClientType::withTrashed()->find($id);

        if (!$clientType) {
            return redirect()->back()->with('error', 'Client Type not found.');
        }

        $clientTypeActivities = Activity::with('causer')
            ->where('subject_type', ClientType::class)
            ->where('subject_id', $clientType->id)
            ->latest()
            ->get();

        return view('admin.client_type.activities', compact('clientType', 'clientTypeActivities'));
    }

    public function index()
    {
        $data = ClientType::orderby('id','DESC')->select('id', 'name')->get();
        return view('admin.client_type.index', compact('data'));
    }

    public function store(Request $request)
    {
        if(empty($request->name)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Name \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }

        $chkname = ClientType::where('name',$request->name)->first();
        if($chkname){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>This name already added.</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        $data = new ClientType;
        $data->name = $request->name;
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
        $info = ClientType::where($where)->get()->first();
        return response()->json($info);
    }

    public function update(Request $request)
    {
        if(empty($request->name)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Name \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }

        $duplicatename = ClientType::where('name',$request->name)->where('id','!=', $request->codeid)->first();
        if($duplicatename){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>This name already added.</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }

        $data = ClientType::find($request->codeid);
        $data->name = $request->name;
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
        if(ClientType::destroy($id)){
            return response()->json(['success'=>true,'message'=>'Data has been deleted successfully']);
        }else{
            return response()->json(['success'=>false,'message'=>'Delete Failed']);
        }
    }
}
