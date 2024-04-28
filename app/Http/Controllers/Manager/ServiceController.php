<?php

namespace App\Http\Controllers\Manager;

use DataTables;
use Illuminate\Http\Request;
use App\Models\ClientService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    public function getAllAssignedServices(Request $request)
    {
        $currentUserId = Auth::id();
        if ($request->ajax()) {
            $data = ClientService::with('clientSubServices')
            ->where('manager_id', $currentUserId)
            ->whereDate('service_deadline', '<=', now()->addDays(30))
            ->orderBy('id', 'desc')
            ->get();

            return DataTables::of($data)
            
                ->addColumn('clientname', function(ClientService $clientservice) {
                    return $clientservice->client->name;
                })
                ->addColumn('servicename', function(ClientService $clientservice) {
                    return $clientservice->service->name;
                })
                ->make(true);
        }
    }
}
