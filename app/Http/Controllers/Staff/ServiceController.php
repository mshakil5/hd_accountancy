<?php

namespace App\Http\Controllers\Staff;

use DataTables;
use App\Models\Client;
use App\Models\Service;
use App\Models\ServiceStaff;
use Illuminate\Http\Request;
use App\Models\ClientService;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ServiceController extends Controller
{

   public function getServicesClientStaff(Request $request)
    {
        if ($request->ajax()) {
            $staffId = auth()->id();

            $data = ServiceStaff::with(['client', 'staff'])
                ->where('staff_id', $staffId)
                ->orderBy('id', 'desc')
                ->get();

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


    public function updateServiceStatus(Request $request)
    {
        if ($request->ajax()) {
            $serviceStaff = ServiceStaff::find($request->input('service_staff_id'));
            if ($serviceStaff) {
                $serviceStaff->status = $request->input('status');
                $serviceStaff->save();
                return response()->json(['success' => true]);
            }
            return response()->json(['success' => false]);
        }
    }
}
