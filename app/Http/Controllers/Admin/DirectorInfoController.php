<?php

namespace App\Http\Controllers\Admin;

use App\Models\Client;
use App\Models\DirectorInfo;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DirectorInfoController extends Controller
{
    public function index()
    {
        $clients = Client::orderby('id','DESC')->get();
        $data = DirectorInfo::with('client')->orderBy('id','DESC')->get();
        return view('admin.director_info.index', compact('clients','data'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required',
            'name' => 'required|string',
            'last_name' => 'required|string',
            'phone' => 'required',
            'email' => 'required',
            'address' => 'required',
            'dob' => 'required',
            'address_line_2' => 'nullable',
            'city' => 'nullable',
            'country' => 'nullable',
            'post_code' => 'nullable',
            'photo_id_saved' => 'nullable',
            'ni_number' => 'required', 
            'directors_tax_return' => 'nullable',
            'dir_verification_code' => 'nullable',
            'hmrc_authorisation' => 'nullable',
            'utr_number' => 'nullable', 
            'utr_authorization' => 'nullable', 
            'nino' => 'nullable', 
        ],[
            'client_id.required' => 'The client reference id field is required.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $data = new DirectorInfo;

        $data->client_id = $request->client_id;
        $data->name = $request->name;
        $data->last_name = $request->last_name;
        $data->phone = $request->phone;
        $data->email = $request->email;
        $data->address = $request->address;
        $data->address_line_2 = $request->address_line_2;
        $data->city = $request->city;
        $data->country = $request->country;
        $data->post_code = $request->post_code;
        $data->photo_id_saved = $request->photo_id_saved ?? null;
        $data->dob = $request->dob;
        $data->ni_number = $request->ni_number;
        $data->directors_tax_return = $request->directors_tax_return ?: 0;
        $data->dir_verification_code = $request->dir_verification_code;
        $data->hmrc_authorisation = $request->hmrc_authorisation ?: 0;
        $data->utr_number = $request->utr_number;
        $data->utr_authorization = $request->utr_authorization ?: 0;
        $data->nino = $request->nino;
        $data->created_by = Auth::id();

        if ($data->save()) {
            return response()->json(['status' => 200, 'message' => 'Director info created successfully']);
        } else {
            return response()->json(['status' => 500, 'message' => 'Server Error']);
        }
    }

    public function edit($id)
    {
        $where = [
            'id'=>$id
        ];
        $info = DirectorInfo::where($where)->get()->first();
        return response()->json($info);
    }

    public function update(Request $request)
    {
        if(empty($request->name)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Name \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        // if(empty($request->phone)){
        //     $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"phone\" field.</b></div>";
        //     return response()->json(['status'=> 303,'message'=>$message]);
        //     exit();
        // } elseif (!preg_match('/^\d{11}$/', $request->phone)) {
        //     $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Phone number should be 11 digits.</b></div>";
        //     return response()->json(['status'=> 303,'message'=>$message]);
        //     exit();
        // }
        // if(empty($request->email)){
        //     $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"email \" field..!</b></div>";
        //     return response()->json(['status'=> 303,'message'=>$message]);
        //     exit();
        // }
        // if(empty($request->address)){
        //     $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"address \" field..!</b></div>";
        //     return response()->json(['status'=> 303,'message'=>$message]);
        //     exit();
        // }
        // if(empty($request->dob)){
        //     $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"date of birth \" field..!</b></div>";
        //     return response()->json(['status'=> 303,'message'=>$message]);
        //     exit();
        // }
        
        // if(empty($request->ni_number)){
        //     $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"NI number \" field..!</b></div>";
        //     return response()->json(['status'=> 303,'message'=>$message]);
        //     exit();
        // }
        // if(empty($request->utr_number)){
        //     $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"UTR Number \" field..!</b></div>";
        //     return response()->json(['status'=> 303,'message'=>$message]);
        //     exit();
        // }
        // if(empty($request->utr_authorization)){
        //     $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Utr authorization \" field..!</b></div>";
        //     return response()->json(['status'=> 303,'message'=>$message]);
        //     exit();
        // }
        // if(empty($request->nino)){
        //     $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Nino \" field..!</b></div>";
        //     return response()->json(['status'=> 303,'message'=>$message]);
        //     exit();
        // }
        // if(empty($request->status) && $request->status !== '0'){
        //     $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Company status \" field..!</b></div>";
        //     return response()->json(['status'=> 303,'message'=>$message]);
        //     exit();
        // }

        $data = DirectorInfo::find($request->codeid);

        $data->name = $request->name;
        $data->phone = $request->phone;
        $data->email = $request->email;
        $data->address = $request->address;
        $data->dob = $request->dob;
        $data->ni_number = $request->ni_number;
        $data->utr_number = $request->utr_number;
        $data->utr_authorization = $request->utr_authorization;
        $data->directors_tax_return = $request->directors_tax_return;
        $data->nino = $request->nino;
        $data->ni_number = $request->ni_number;
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
        if(DirectorInfo::destroy($id)){
            return response()->json(['success'=>true,'message'=>'Data has been deleted successfully']);
        }else{
            return response()->json(['success'=>false,'message'=>'Delete Failed']);
        }
    }

}
