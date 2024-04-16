<?php

namespace App\Http\Controllers\Admin;

use DataTables;
use App\Models\User;
use App\Models\Client;
use App\Models\Service;
use App\Models\ClientType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{

    public function index()
    {
        return view('admin.client.index');
    }

    public function getClients(Request $request)
    {
        if ($request->ajax()) {
            $data = Client::with(['clientType', 'manager'])->orderBy('id', 'desc')->get();
            return Datatables::of($data)
                ->addIndexColumn()
                ->make(true);
        }
    }

    public function create()
    {
        $managers = User::where('type', '2')->orderby('id','DESC')->get();
        $clients = Client::with(['clientType', 'manager'])->orderBy('id', 'DESC')->get();
        $clientTypes = ClientType::orderby('id','DESC')->get();
        $services = Service::orderby('id','DESC')->get();
        return view('admin.client.create', compact('clientTypes','managers','clients','services'));
    }

    public function store(Request $request)
    {
         $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'client_type_id' => 'required',
            'manager_id' => 'required',
            'email' => 'required|email',
            'phone' => 'required|numeric|digits:11',
            'address_line1' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'postcode' => 'required|string',
            'country' => 'required|string|max:255',
            'photo' => 'nullable|mimes:jpeg,png,jpg,gif,svg,pdf|max:8048',
            'photo_id' => 'nullable|mimes:jpeg,png,jpg,gif,svg,pdf|max:8048',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }
        
        $data = new Client;

        if ($request->hasFile('photo')) {
            $imageName = time() . '_' . $request->photo->getClientOriginalName();
            $request->photo->move(public_path('images/client/photo'), $imageName);
            $data->photo = $imageName;
        }

        if ($request->hasFile('photo_id')) {
            $imageName = time() . '_' . $request->photo_id->getClientOriginalName();
            $request->photo_id->move(public_path('images/client/photo_id'), $imageName);
            $data->photo_id = $imageName;
        }

        $data->name = $request->name;
        $data->refid = mt_rand(100000, 999999);
        $data->client_type_id = $request->client_type_id;
        $data->manager_id = $request->manager_id;
        $data->email = $request->email;
        $data->phone = $request->phone;
        $data->address_line1 = $request->address_line1;
        $data->address_line2 = $request->address_line2;
        $data->city = $request->city;
        $data->state = $request->state;
        $data->postcode = $request->postcode;
        $data->country = $request->country;
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
        $info = Client::where($where)->with('services')->get()->first();
        return response()->json($info);
    }

    public function update(Request $request)
    {
        if(empty($request->name)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Name \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        if(empty($request->email)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Email \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        $duplicateemail = Client::where('email',$request->email)->where('id','!=', $request->codeid)->first();
        if($duplicateemail){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>This email already added.</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        if(empty($request->phone)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Phone \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        if(empty($request->address_line1)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Address \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        if(empty($request->trading_address)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Trading address\" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        if(empty($request->city)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"City \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        if(empty($request->postcode)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Postal code \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        if(empty($request->country)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Country \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }

        $data = Client::find($request->codeid);

        if($request->photo != 'null'){
            $request->validate([
                'photo' => 'required|mimes:jpeg,png,jpg,gif,svg,pdf|max:8048',
            ]);

            if ($data->photo && file_exists(public_path('images/client/photo/' . $data->photo))) {
                unlink(public_path('images/client/photo/' . $data->photo));
            }

            $rand = mt_rand(100000, 999999);
            $imageName = time(). $rand .'.'.$request->photo->extension();
            $request->photo->move(public_path('images/client/photo'), $imageName);
            $data->photo = $imageName;
        }

        if($request->photo_id != 'null'){
            $request->validate([
                'photo_id' => 'required|mimes:jpeg,png,jpg,gif,svg,pdf|max:8048',
            ]);

            if ($data->photo_id && file_exists(public_path('images/client/photo_id/' . $data->photo_id))) {
                unlink(public_path('images/client/photo_id/' . $data->photo_id));
            }

            $rand = mt_rand(100000, 999999);
            $imageName = time(). $rand .'.'.$request->photo_id->extension();
            $request->photo_id->move(public_path('images/client/photo_id'), $imageName);
            $data->photo_id = $imageName;
        }

        $data->name = $request->name;
        $data->client_type_id  = $request->client_type_id;
        $data->manager_id  = $request->manager_id;
        $data->email = $request->email;
        $data->phone = $request->phone;
        $data->address_line1 = $request->address_line1;
        $data->address_line2 = $request->address_line2;
        $data->address_line3 = $request->address_line3;
        $data->address_line3 = $request->address_line3;
        $data->trading_address = $request->trading_address;
        $data->city = $request->city;
        $data->state = $request->state;
        $data->postcode = $request->postcode;
        $data->country = $request->country;
        $data->updated_by =  Auth::id();
        $services = $request->input('services', []);
        $data->services()->sync($services);

        if ($data->save()) {
        $message = "<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Data Updated Successfully.</b></div>";
        return response()->json(['status' => 300, 'message' => $message]);
        } else {
            return response()->json(['status' => 303, 'message' => 'Server Error!!']);
        }
    }

    public function delete($id)
    {
        if(Client::destroy($id)){
            return response()->json(['success'=>true,'message'=>'Client has been deleted successfully']);
        }else{
            return response()->json(['success'=>false,'message'=>'Delete Failed']);
        }
    }

    public function changeStatus(Request $request)
    {
        $client = Client::find($request->client_id);
        if ($client) {
            $client->status = !$client->status;
            $client->save();
            return response()->json(['success' => true]);
        } else {
            return response()->json(['success' => false]);
        }
    }


}
