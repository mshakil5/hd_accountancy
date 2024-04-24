<?php

namespace App\Http\Controllers\Admin;

use App\Models\Client;
use App\Models\ContactInfo;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ContactInfoController extends Controller
{
    public function index()
    {
        $clients = Client::orderby('id','DESC')->get();
        $data = ContactInfo::with('client')->orderBy('id', 'DESC')->get();
        return view('admin.contact_info.index', compact('clients','data'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(),[
        'client_id' => 'required',
        'greeting' => 'required',
        'first_name' => 'required',
        'last_name' => 'required',
        'job_title' => 'required',
        'email' => 'required',
        'phone' => 'required|numeric|digits:11',
        
        ],[
            'client_id.required' => 'The client reference id field is required.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $data = new ContactInfo;

        $data->client_id = $request->client_id;
        $data->greeting = $request->greeting;
        $data->first_name = $request->first_name;
        $data->last_name = $request->last_name;
        $data->job_title = $request->job_title;
        $data->email = $request->email;
        $data->phone = $request->phone;
        $data->created_by = Auth::id();

        if ($data->save()) {
            return response()->json(['status' => 200, 'message' => 'Contact info created successfully']);
        } else {
            return response()->json(['status' => 500, 'message' => 'Server Error']);
        }
    }

    public function edit($id)
    {
        $where = [
            'id'=>$id
        ];
        $info = ContactInfo::where($where)->get()->first();
        return response()->json($info);
    }

    public function update(Request $request)
    {
        if(empty($request->greeting)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"greeting \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        if(empty($request->first_name)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"First name \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        if(empty($request->last_name)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"last name \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        if(empty($request->job_title)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"job title \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        if(empty($request->email)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"email \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        $duplicateemail = ContactInfo::where('email',$request->email)->where('id','!=', $request->codeid)->first();
        if($duplicateemail){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>This email already added.</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        if(empty($request->phone)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"phone \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        if (!preg_match('/^\d{11}$/', $request->phone)) {
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Phone number must be 11 digits.</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }

        $data = ContactInfo::find($request->codeid);
        $data->greeting = $request->greeting;
        $data->first_name = $request->first_name;
        $data->last_name = $request->last_name;
        $data->job_title = $request->job_title;
        $data->email = $request->email;
        $data->phone = $request->phone;
        $data->updated_by = Auth::id();
        if ($data->save()) {
            $message ="<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Data Updated Successfully.</b></div>";
            return response()->json(['status'=> 300,'message'=>$message]);
        }else{
            return response()->json(['status'=> 303,'message'=>'Server Error!!']);
        }
    }

    public function delete($id)
    {
        if(ContactInfo::destroy($id)){
            return response()->json(['success'=>true,'message'=>'Data has been deleted successfully']);
        }else{
            return response()->json(['success'=>false,'message'=>'Delete Failed']);
        }
    }
}
 