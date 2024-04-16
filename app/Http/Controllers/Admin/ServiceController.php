<?php

namespace App\Http\Controllers\Admin;

use DataTables;
use App\Models\Client;
use App\Models\Service;
use App\Models\ServiceStaff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ServiceController extends Controller
{
    public function index()
    {
        $data = Service::orderby('id','DESC')->get();
        return view('admin.service.index', compact('data'));
    }

    public function store(Request $request)
    {
        if(empty($request->name)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Name \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        if(empty($request->tag)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Tag \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        $chkname = Service::where('name',$request->name)->first();
        if($chkname){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>This name already added.</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        $data = new Service;
        $data->name = $request->name;
        $data->tag = $request->tag;
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
        $info = Service::where($where)->get()->first();
        return response()->json($info);
    }

    public function update(Request $request)
    {
        if(empty($request->name)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Username \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }

        $duplicatename = Service::where('name',$request->name)->where('id','!=', $request->codeid)->first();
        if($duplicatename){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>This name already added.</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }

        $data = Service::find($request->codeid);
        $data->name = $request->name;
        $data->tag = $request->tag;
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
        if(Service::destroy($id)){
            return response()->json(['success'=>true,'message'=>'Data has been deleted successfully']);
        }else{
            return response()->json(['success'=>false,'message'=>'Delete Failed']);
        }
    }

    public function serviceAssign(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required',
            'services' => 'required|array|min:1',
        ], [
            'client_id.required' => 'The client reference id field is required.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $client = Client::find($request->client_id);
        $services = $request->input('services');

        if (!$client) {
            return response()->json(['status' => 500, 'message' => 'Client not found'], 500);
        }

        $client->services()->attach($services);

        return response()->json(['status' => 200, 'message' => 'Services assigned successfully']);
    }

    public function getAssignedServices($client_id)
    {
        $client = DB::table('clients')->find($client_id);
        
        if (!$client) {
            return response()->json(['status' => 'error', 'message' => 'Client not found'], 404);
        }

        $assignedServices = DB::table('client_service')
            ->join('services', 'client_service.service_id', '=', 'services.id')
            ->select('services.id', 'services.name')
            ->where('client_service.client_id', $client_id)
            ->get();

        return response()->json(['status' => 'success', 'assigned_services' => $assignedServices]);
    }

    public function assignServiceStaff(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required',
            'staff_id' => 'required',
            'deadline' => 'required|date',
            'note' => 'required|string',
            'assigned_services' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
            }

            $serviceStaff = new ServiceStaff;

            $serviceStaff->client_id = $request->client_id;
            $serviceStaff->staff_id = $request->staff_id;
            $serviceStaff->assigned_services = $request->assigned_services;
            $serviceStaff->deadline = $request->deadline;
            $serviceStaff->note = $request->note;
            $serviceStaff->created_by = auth()->user()->id;

            if ($serviceStaff->save()) {
                return response()->json(['status' => 200, 'message' => 'Service staff assigned successfully.'], 200);
            } else {
                return response()->json(['status' => 500, 'message' => 'Failed to assign service staff.'], 500);
            }

    }

    public function getAllServices()
    {
         $services = Service::orderBy('id', 'desc')->get();
         return response()->json(['status' => 200, 'services' => $services], 200);
    }

    public function createSpecificService(Request $request) 
    {
        $validator = Validator::make($request->all(), [
           'name' => 'required|string|max:255|unique:services',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $service = new Service;
        $service->name = $request->input('name');
        $service->created_by = Auth::id();

        if ($service->save()) {
            return response()->json(['status' => 200, 'message' => 'Specific service created successfully'], 200);
        } else {
            return response()->json(['status' => 500, 'message' => 'Failed to create'], 500);
        }
    }

    public function getServicesClientStaff(Request $request)
    {
        if ($request->ajax()) {
            $data = ServiceStaff::with(['client', 'staff'])->orderBy('id', 'desc')->get();

            $data->transform(function ($item, $key) {
                $assigned_services = $item->assigned_services;
                $services_names = [];

                foreach ($assigned_services as $service_id) {
                    $service = Service::find($service_id);
                    if ($service) {
                        $services_names[] = $service->name;
                    }
                }
                $item->assigned_services = implode(', ', $services_names);

                return $item;
            });

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('client_name', function($row) {
                    return $row->client ? $row->client->name : '';
                })
                ->addColumn('tasks', function($row) {
                    return $row->assigned_services;
                })
                ->addColumn('staff_name', function($row) {
                    return $row->staff ? $row->staff->first_name : '';
                })
                ->rawColumns(['client_name', 'tasks', 'staff_name'])
                ->make(true);
        }

    }

}
