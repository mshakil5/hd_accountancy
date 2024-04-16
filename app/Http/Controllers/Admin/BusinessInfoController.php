<?php

namespace App\Http\Controllers\Admin;

use App\Models\Client;
use App\Models\BusinessInfo;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BusinessInfoController extends Controller
{
    public function index()
    {
        $clients = Client::orderby('id','DESC')->get();
        $data = BusinessInfo::with('client')->orderBy('id', 'DESC')->get();
        return view('admin.business_info.index', compact('clients','data'));
    }

    public function create()
    {
        $clients = Client::orderby('id','DESC')->get();
        return view('admin.business_info.create', compact('clients'));
    }
    
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required',
            'nature_of_business' => 'required|string',
            'company_number' => 'required',
            'due_date' => 'required',
            'confirmation_due_date' => 'same:due_date',
            'authorization_code' => 'required',
            'company_utr' => 'required',
            'status' => 'required',
        ],[
            'client_id.required' => 'The client reference id field is required.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $data = new BusinessInfo;

        $data->client_id = $request->client_id;
        $data->nature_of_business = $request->nature_of_business;
        $data->company_number = $request->company_number;
        $data->year_end_date = $request->year_end_date;
        $data->due_date = $request->due_date;
        $data->confirmation_due_date = $request->confirmation_due_date;
        $data->authorization_code = $request->authorization_code;
        $data->company_utr = $request->company_utr;
        $data->status = $request->status;
        $data->created_by = Auth::id();

        if ($data->save()) {
            return response()->json(['status' => 200, 'message' => 'Client created successfully']);
        } else {
            return response()->json(['status' => 500, 'message' => 'Server Error']);
        }

    }

    public function edit($id)
    {
        $where = [
            'id'=>$id
        ];
        $info = BusinessInfo::where($where)->get()->first();
        return response()->json($info);
    }

    public function update(Request $request)
    {
        if(empty($request->nature_of_business)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please choose \"Nature of business \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        if(empty($request->company_number)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please choose \"Company number \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        if(empty($request->year_end_date)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please choose \"Year end date \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        if(empty($request->due_date)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please choose \"Due date \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        if(empty($request->confirmation_due_date)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Confirmation due date \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        if(isset($request->due_date) && ($request->due_date != $request->confirmation_due_date)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Due date doesn't match.</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        if(empty($request->authorization_code)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Authorization code \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        if(empty($request->company_utr)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Company Utr \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        if(empty($request->company_status) && $request->company_status !== '0'){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Company status \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        
        $data = BusinessInfo::find($request->codeid);
        $data->nature_of_business = $request->nature_of_business;
        $data->company_number = $request->company_number;
        $data->year_end_date = $request->year_end_date;
        $data->due_date = $request->due_date;
        $data->confirmation_due_date = $request->confirmation_due_date;
        $data->authorization_code = $request->authorization_code;
        $data->company_utr = $request->company_utr;
        $data->status = $request->status;
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
        if(BusinessInfo::destroy($id)){
            return response()->json(['success'=>true,'message'=>'Data has been deleted successfully']);
        }else{
            return response()->json(['success'=>false,'message'=>'Delete Failed']);
        }
    }
}
