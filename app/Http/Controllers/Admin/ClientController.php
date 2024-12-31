<?php

namespace App\Http\Controllers\Admin;

use DataTables;
use App\Models\User;
use App\Models\Client;
use App\Models\Service;
use App\Models\ClientType;
use App\Models\ContactInfo;
use App\Models\BusinessInfo;
use App\Models\DirectorInfo;
use Illuminate\Http\Request;
use App\Models\ClientService;
use App\Models\ClientSubService;
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
            $data = Client::with(['manager:id,first_name'])
                          ->select('id', 'refid', 'name', 'phone', 'email', 'status')
                          ->orderBy('id', 'desc')
                          ->get();
    
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('manager_first_name', function ($row) {
                    return $row->manager->first_name ?? '';
                })
                ->rawColumns(['manager_first_name'])
                ->make(true);
        }
    }    

    public function indexStaff()
    {
        return view('staff.client.index');
    }

    public function getClientsStaff(Request $request)
    {
        if ($request->ajax()) {
            $authUserId = auth()->id();
            $data = Client::with(['clientType', 'manager'])
                ->whereHas('clientSubServices', function ($query) use ($authUserId) {
                    $query->where('staff_id', $authUserId);
                })
                ->orderBy('id', 'desc')
                ->get();
    
            return Datatables::of($data)
                ->addIndexColumn()
                ->editColumn('manager.first_name', function ($row) {
                    return $row->manager->first_name ?? '';
                })
                ->make(true);
        }
    }    

    public function indexManager()
    {
        return view('manager.client.index');
    }

    public function getClientsManager(Request $request)
    {
        $authUserId = auth()->id();
        if ($request->ajax()) {
            $data = Client::with(['clientType', 'manager'])
                ->where(function ($query) use ($authUserId) {
                    $query->where('manager_id', $authUserId)
                          ->orWhereHas('clientSubServices', function ($subQuery) use ($authUserId) {
                              $subQuery->where('staff_id', $authUserId);
                          });
                })
                ->distinct()
                ->orderBy('id', 'desc')
                ->get();
    
            return Datatables::of($data)
                ->addIndexColumn()
                ->editColumn('manager.first_name', function ($row) {
                    return $row->manager->first_name ?? '';
                })
                ->make(true);
        }
    }

    public function create()
    {
        $managers = User::where('type', '2')->select('id', 'first_name')->orderby('id', 'DESC')->get();
        $staffs = User::where('type', '3')->select('id', 'first_name')->orderby('id', 'DESC')->get();
        $clientTypes = ClientType::select('id', 'name')->orderby('id', 'DESC')->get();
        $services = Service::select('id', 'name')->orderby('id', 'DESC')->get();
        $client = "";
        return view('admin.client.create', compact('clientTypes', 'managers', 'services', 'client', 'staffs'));
    }

    public function store(Request $request)
    {
         $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'client_type_id' => 'required',
            'manager_id' => 'required',
            'email' => 'required|email|unique:clients',
            'phone' => 'required|numeric|digits:11',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'trading_address' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'town' => 'required|string|max:255',
            'reference_id' => 'required',
            'postcode' => 'required|string',
            'country' => 'nullable|string|max:255',
            'photo' => 'nullable|mimes:jpeg,png,jpg,gif,svg,pdf|max:8048',
            'photo_id' => 'nullable|mimes:jpeg,png,jpg,gif,svg,pdf|max:8048'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }
        
        $data = new Client;

        $data->name = $request->name;
        $data->refid = $request->reference_id;
        $data->client_type_id = $request->client_type_id;
        $data->manager_id = $request->manager_id;
        $data->email = $request->email;
        $data->phone = $request->phone;
        $data->address_line1 = $request->address_line1;
        $data->address_line2 = $request->address_line2;
        $data->address_line3 = $request->address_line3;
        $data->trading_address = $request->trading_address;
        $data->city = $request->city;
        $data->town = $request->town;
        $data->postcode = $request->postcode;
        $data->country = $request->country;
        $data->photo_id = $request->photo_id;
        $data->created_by = Auth::id();

        if ($request->hasFile('photo')) {
            $imageName = time() . '_' . $request->photo->getClientOriginalName();
            $request->photo->move(public_path('images/client'), $imageName);
            $data->photo = $imageName;
        }

        if ($request->hasFile('photo_id')) {
            $photoIdName = time() . '_' . $request->photo_id->getClientOriginalName();
            $request->photo_id->move(public_path('images/client_photo_id'), $photoIdName);
            $data->photo_id = $photoIdName;
        }

        if ($data->save()) {
             return response()->json(['status' => 200, 'message' => 'Client created successfully', 'client_id' => $data->id]);
        } else {
            return response()->json(['status' => 500, 'message' => 'Server Error']);
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

    public function updateForm($id)
    {
        $client = Client::with(['clientType', 'manager', 'businessInfo', 'directorInfos', 'clientServices', 'contactInfos', 'recentUpdates.user'])->find($id);
        $client->recentUpdates = $client->recentUpdates()->orderBy('id', 'desc')->get();
        $clientTypes = ClientType::select('id', 'name')->orderby('id', 'DESC')->get();
        $managers = User::where('type', '2')->select('id', 'first_name')->orderby('id', 'DESC')->get();
        $businessInfo = BusinessInfo::where('client_id', $id)->first();
        $directorInfos = DirectorInfo::where('client_id', $id)->get();
        $contactInfo = ContactInfo::where('client_id', $id)->first();
        $services = Service::select('id', 'name')->orderby('id', 'DESC')->get();
        $staffs = User::whereIn('type', ['3', '2'])->select('id', 'first_name')->orderby('id', 'DESC')->get();
        return view('admin.client.updateForm', compact('client', 'clientTypes', 'managers', 'businessInfo', 'directorInfos', 'contactInfo', 'services', 'staffs'));
    }

    public function updateClientDetails(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'client_type_id' => 'required',
            'manager_id' => 'required',
            'reference_id' => 'required',
            'email' => 'required|email',
            'phone' => 'required|numeric|digits:11',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'trading_address' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'town' => 'required|string|max:255',
            'postcode' => 'required|string',
            'country' => 'required|string|max:255',
            'photo' => 'nullable|mimes:jpeg,png,jpg,gif,svg,pdf|max:8048',
            'photo_id' => 'nullable|mimes:jpeg,png,jpg,gif,svg,pdf|max:8048'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }
        
        $client = Client::find($id);

        if (!$client) {
            return response()->json(['status' => 404, 'message' => 'Client not found'], 404);
        }

        $client->name = $request->name;
        $client->refid = $request->reference_id;
        $client->client_type_id = $request->client_type_id;
        $client->manager_id = $request->manager_id;
        $client->email = $request->email;
        $client->phone = $request->phone;
        $client->address_line1 = $request->address_line1;
        $client->address_line2 = $request->address_line2;
        $client->address_line3 = $request->address_line3;
        $client->trading_address = $request->trading_address;
        $client->city = $request->city;
        $client->town = $request->town;
        $client->postcode = $request->postcode;
        $client->country = $request->country;
        $client->updated_by = Auth::id();

        if ($request->hasFile('photo')) {
            if (!empty($client->photo)) {
                $oldImagePath = public_path('images/client') . '/' . $client->photo;
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            $imageName = time() . '_' . $request->photo->getClientOriginalName();
            $request->photo->move(public_path('images/client'), $imageName);
            $client->photo = $imageName;
        }

        if ($request->hasFile('photo_id')) {
            $photoIdName = time() . '_' . $request->photo_id->getClientOriginalName();
            $request->photo_id->move(public_path('images/client_photo_id'), $photoIdName);
        
            if (!empty($client->photo_id)) {
                $oldPhotoIdPath = public_path('images/client_photo_id') . '/' . $client->photo_id;
                if (file_exists($oldPhotoIdPath)) {
                    unlink($oldPhotoIdPath);
                }
            }
        
            $client->photo_id = $photoIdName;
        }

        if ($client->save()) {
            return response()->json(['status' => 200, 'message' => 'Client details updated successfully', 'client_id' => $client->id]);
        } else {
            return response()->json(['status' => 500, 'message' => 'Server Error']);
        }
    }

    public function updateClientBusinessInfo(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nature_of_business' => 'required|string',
            'company_number' => 'required|string',
            'year_end_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'confirmation_due_date' => 'nullable|date|same:due_date',
            'authorization_code' => 'nullable|string',
            'company_utr' => 'nullable|string',
            'status' => 'required',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }
    
        $businessInfo = BusinessInfo::updateOrCreate(
            ['client_id' => $id],
            [
                'nature_of_business' => $request->nature_of_business,
                'company_number' => $request->company_number,
                'year_end_date' => $request->year_end_date,
                'due_date' => $request->due_date,
                'confirmation_due_date' => $request->confirmation_due_date,
                'authorization_code' => $request->authorization_code,
                'company_utr' => $request->company_utr,
                'hmrc_authorisation' => $request->hmrc_authorisation,
                'vat_number' => $request->vat_number,
                'status' => $request->status,
                'updated_by' => Auth::id(),
            ]
        );
    
        return response()->json([
            'status' => 200,
            'message' => 'Business info updated successfully'
        ]);
    }

    public function destroyDirector($id)
    {
        $director = DirectorInfo::findOrFail($id);
        $director->delete();
        return response()->json(['message' => 'Director deleted successfully']);
    }

    public function updateClientDirectorInfo(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'phone' => 'required',
            'email' => 'required',
            'address' => 'nullable',
            'dob' => 'nullable',
            'ni_number' => 'nullable', 
            'utr_number' => 'nullable', 
            'utr_authorization' => 'nullable', 
            'nino' => 'nullable', 
        ], [
            'client_id.required' => 'The client reference id field is required.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $director = DirectorInfo::findOrFail($id);

        $director->name = $request->name;
        $director->phone = $request->phone;
        $director->email = $request->email;
        $director->address = $request->address;
        $director->dob = $request->dob;
        $director->ni_number = $request->ni_number;
        $director->utr_number = $request->utr_number;
        $director->utr_authorization = $request->utr_authorization;
        $director->nino = $request->nino;

        if ($director->save()) {
            return response()->json(['status' => 200, 'message' => 'Director info updated successfully']);
        } else {
            return response()->json(['status' => 500, 'message' => 'Server Error']);
        }
    }

    public function getClientServices($clientId)
    {
        $allServices = Service::orderBy('id', 'desc')->get();
        $clientServices = ClientService::where('client_id', $clientId)->first();
        $assignedServices = [];
        if ($clientServices) {
            $serviceIds = $clientServices->assigned_services;
            $assignedServices = Service::whereIn('id', $serviceIds)->get();
            $deadline = $clientServices->deadline;
        } else {
            $assignedServices = collect([]);
            $deadline = null;
        }

        $responseData = [
            'status' => 200,
            'all_services' => $allServices,
            'assigned_services' => $assignedServices,
            'deadline' => $deadline
        ];

        return response()->json($responseData);
    }

    public function updateClientServices(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            // 'services' => 'required|array|min:1',
            // 'deadline' => 'required|date',
        ], [
            // 'deadline.required' => 'The deadline field is required.',
            // 'deadline.date' => 'The deadline must be a valid date.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $client = Client::find($id);

        if (!$client) {
            return response()->json(['status' => 500, 'message' => 'Client not found'], 500);
        }

        $clientService = ClientService::where('client_id', $id)->first();
        if ($clientService) {
            $clientService->assigned_services = $request->input('services');
            $clientService->deadline = $request->deadline;
            $clientService->save();
        } else {
            $clientService = new ClientService();
            $clientService->client_id = $id;
            $clientService->assigned_services = $request->input('services');
            $clientService->deadline = $request->deadline;
            $clientService->save();
        }

        return response()->json(['status' => 200, 'message' => 'Services assigned successfully']);
    } 

    public function updateClientContactInfo(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'greeting' => 'required',
            'first_name' => 'required',
            'last_name' => 'required',
            'job_title' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $contactInfo = ContactInfo::findOrFail($id);

        $contactInfo->greeting = $request->greeting;
        $contactInfo->first_name = $request->first_name;
        $contactInfo->last_name = $request->last_name;
        $contactInfo->job_title = $request->job_title;
        $contactInfo->email = $request->email;
        $contactInfo->phone = $request->phone;

        if ($contactInfo->save()) {
            return response()->json(['status' => 200, 'message' => 'Contact info updated successfully']);
        } else {
            return response()->json(['status' => 500, 'message' => 'Server Error']);
        }
    }

    public function deleteClient($id)
    {
        $client = Client::findOrFail($id);
    
        if (!empty($client->photo)) {
            $photoPath = public_path('images/client') . '/' . $client->photo;
            if (file_exists($photoPath)) {
                unlink($photoPath);
            }
        }

        if (!empty($client->photo_id)) {
            $photoIdPath = public_path('images/client_photo_id') . '/' . $client->photo_id;
            if (file_exists($photoIdPath)) {
                unlink($photoIdPath);
            }
        }
    
        $client->delete();
    
        return response()->json([
            'status' => 200,
            'message' => 'Client deleted successfully.'
        ]);
    }    

    public function destroyContact($id)
    {
        $director = ContactInfo::findOrFail($id);
        $director->delete();
        return response()->json(['message' => 'Director deleted successfully']);
    }

}
