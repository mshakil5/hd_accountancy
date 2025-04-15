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
use Spatie\Activitylog\Models\Activity;
use App\Models\AccountancyFee;
use Illuminate\Support\Facades\Hash;

class ClientController extends Controller
{

    public function index()
    {
        return view('admin.client.index');
    }

    public function getClients(Request $request)
    {
        $authUserId = auth()->id();
        $filter = $request->input('filter', 'all');
    
        if ($request->ajax()) {
            $query = Client::with(['clientType', 'manager', 'clientSubServices']);
        
            if ($filter == 'assigned') {
                $query->whereHas('clientSubServices', function ($subQuery) use ($authUserId) {
                    $subQuery->where('staff_id', $authUserId);
                })
                ->orWhereHas('clientServices', function ($subQuery) use ($authUserId) {
                    $subQuery->where('manager_id', $authUserId);
                })
                ->orWhere('manager_id', $authUserId);
            }
        
            $data = $query->distinct()->orderBy('id', 'desc')->get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('manager_first_name', function ($row) {
                    return $row->manager->first_name ?? '';
                })
                ->addColumn('client_type_name', function ($row) {
                    return $row->clientType->name ?? '';
                })
                ->rawColumns(['manager_first_name', 'client_type_name'])
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
            $filter = $request->input('filter', 'all');
    
            $query = Client::with(['clientType', 'manager']);
    
            if ($filter == 'assigned') {
                $query->whereHas('clientSubServices', function ($query) use ($authUserId) {
                    $query->where('staff_id', $authUserId);
                })
                ->orWhereHas('clientServices', function ($subQuery) use ($authUserId) {
                    $subQuery->where('manager_id', $authUserId);
                })
                ->orWhere('manager_id', $authUserId);

            }
    
            $data = $query->distinct()->orderBy('id', 'desc')->get();
    
            return Datatables::of($data)
                ->addIndexColumn()
                ->editColumn('manager.first_name', function ($row) {
                    return $row->manager->first_name ?? '';
                })
                ->addColumn('client_type_name', function ($row) {
                    return $row->clientType->name ?? '';
                })
                ->make(true);
        }
    }
    
    public function showUpdate($id)
    {
        $client = Client::with('recentUpdates.user')->findOrFail($id);
        $client->recentUpdates = $client->recentUpdates()->orderBy('id', 'desc')->get();
        return view('staff.client.recent_update', compact('client'));
    }


    public function showUpdateByManager($id)
    {
        $client = Client::with('recentUpdates.user')->findOrFail($id);
        $client->recentUpdates = $client->recentUpdates()->orderBy('id', 'desc')->get();
        return view('manager.client.recent_update', compact('client'));
    }

    public function indexManager()
    {
        return view('manager.client.index');
    }

    public function getClientsManager(Request $request)
    {
        $authUserId = auth()->id();
        $filter = $request->input('filter', 'all');
    
        if ($request->ajax()) {
            $query = Client::with(['clientType', 'manager']);
    
            if ($filter == 'assigned') {
                $query->whereHas('clientSubServices', function ($subQuery) use ($authUserId) {
                    $subQuery->where('staff_id', $authUserId);
                })
                ->orWhereHas('clientServices', function ($subQuery) use ($authUserId) {
                    $subQuery->where('manager_id', $authUserId);
                })
                ->orWhere('manager_id', $authUserId);
            }
    
            $data = $query->distinct()->orderBy('id', 'desc')->get();
    
            return Datatables::of($data)
                ->addIndexColumn()
                ->editColumn('manager.first_name', function ($row) {
                    return $row->manager->first_name ?? '';
                })
                ->addColumn('client_type_name', function ($row) {
                    return $row->clientType->name ?? '';
                })
                ->make(true);
        }
    }

    public function create()
    {
        $managers = User::whereIn('type', ['1', '2'])->select('id', 'first_name', 'last_name', 'type')->orderby('id', 'DESC')->get();
        $clientTypes = ClientType::select('id', 'name')->orderby('id', 'DESC')->get();
        $client = "";
        return view('admin.client.create', compact('clientTypes', 'managers', 'client'));
    }

    public function store(Request $request)
    {
         $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'client_type_id' => 'required',
            'reference_id' => 'required',
            'manager_id' => 'nullable',
            'email' => 'required|email|unique:clients',
            'phone' => 'required|numeric|digits:11',
            'phone2' => 'nullable|numeric|digits:11',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'trading_address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'town' => 'nullable|string|max:255',
            'postcode' => 'nullable|string',
            'country' => 'nullable|string|max:255',
            'photo' => 'nullable|mimes:jpeg,png,jpg,gif,svg,pdf|max:8048',
            'photo_id' => 'nullable|mimes:jpeg,png,jpg,gif,svg,pdf|max:8048'
        ]);

        if ($request->filled('password')) {
            $validator->addRules([
                'password' => 'required|min:6',
                'confirm_password' => 'required|same:password'
            ]);
        }

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
        $data->phone2 = $request->phone2;
        $data->address_line1 = $request->address_line1;
        $data->address_line2 = $request->address_line2;
        $data->address_line3 = $request->address_line3;
        $data->trading_address = $request->trading_address;
        $data->city = $request->city;
        $data->town = $request->town;
        $data->postcode = $request->postcode;
        $data->country = $request->country;
        if ($request->filled('password')) {
            $data->password = Hash::make($request->password);
        }
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
        $client = Client::with(['clientType', 'manager', 'businessInfo', 'directorInfos', 'clientServices', 'contactInfos', 'recentUpdates.user', 'accountancyFee'])->find($id);
        $client->recentUpdates = $client->recentUpdates()->orderBy('id', 'desc')->get();
        $clientTypes = ClientType::select('id', 'name')->orderby('id', 'DESC')->get();
        $managers = User::whereIn('type', ['1', '2'])->select('id', 'first_name', 'last_name', 'type')->orderby('id', 'DESC')->get();
        $businessInfo = BusinessInfo::where('client_id', $id)->first();
        $directorInfos = DirectorInfo::where('client_id', $id)->get();
        $contactInfo = ContactInfo::where('client_id', $id)->first();
        $services = Service::where('status', '1')->select('id', 'name', 'serviceid')->orderby('id', 'DESC')->get();
        $staffs = User::whereIn('type', ['3', '2', '1'])->select('id', 'first_name', 'last_name', 'type')->orderby('id', 'DESC')->get();
        return view('admin.client.updateForm', compact('client', 'clientTypes', 'managers', 'businessInfo', 'directorInfos', 'contactInfo', 'services', 'staffs'));
    }

    public function showClientActivities($id)
    {
        $client = Client::withTrashed()->find($id);

        if (!$client) {
            return redirect()->back()->with('error', 'Client not found.');
        }
    
        $clientActivities = Activity::with('causer')
            ->where('subject_type', Client::class)
            ->where('subject_id', $client->id)
            ->latest()
            ->get();
    
        $businessInfo = $client->businessInfo;
        $businessInfoActivities = $businessInfo
            ? Activity::where('subject_type', BusinessInfo::class)
                ->where('subject_id', $businessInfo->id)
                ->latest()
                ->get()
            : collect();
    
        $accountancyFee = $client->accountancyFee;
        $accountancyFeeActivities = $accountancyFee
            ? Activity::where('subject_type', AccountancyFee::class)
                ->where('subject_id', $accountancyFee->id)
                ->latest()
                ->get()
            : collect();

        $directors = $client->directorInfos;
        $directorActivities = [];
    
        foreach ($directors as $director) {
            $directorActivities[$director->id] = Activity::where('subject_type', DirectorInfo::class)
                ->where('subject_id', $director->id)
                ->latest()
                ->get();
        }

        $contacts = $client->contactInfos;

        $contactActivities = [];

        foreach ($contacts as $contact) {
            $contactActivities[$contact->id] = Activity::where('subject_type', ContactInfo::class)
                ->where('subject_id', $contact->id)
                ->latest()
                ->get();
        }

        $clientServices = $client->clientServices;

        $clientServiceActivities = [];
        foreach ($clientServices as $service) {
            $clientServiceActivities[$service->id] = Activity::where('subject_type', ClientService::class)
                ->where('subject_id', $service->id)
                ->latest()
                ->get();
        }

        $clientSubServiceActivities = [];
        foreach ($clientServices as $service) {
            foreach ($service->clientSubServices as $subService) {
                $clientSubServiceActivities[$subService->id] = Activity::where('subject_type', ClientSubService::class)
                    ->where('subject_id', $subService->id)
                    ->latest()
                    ->get();
            }
        }
    
        return view('admin.client.activities', compact('client', 'clientActivities', 'businessInfoActivities', 'accountancyFeeActivities', 'directors', 'directorActivities', 'contacts', 'contactActivities', 'clientServices', 'clientServiceActivities', 'clientSubServiceActivities'));
    }

    public function updateClientDetails(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'client_type_id' => 'required',
            'manager_id' => 'nullable',
            'reference_id' => 'required',
            'email' => 'required|email',
            'phone' => 'required|numeric|digits:11',
            'phone2' => 'nullable|numeric|digits:11',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'trading_address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'town' => 'nullable|string|max:255',
            'postcode' => 'nullable|string',
            'country' => 'nullable|string|max:255',
            'photo' => 'nullable|mimes:jpeg,png,jpg,gif,svg,pdf|max:8048',
            'photo_id' => 'nullable|mimes:jpeg,png,jpg,gif,svg,pdf|max:8048'
        ]);

        if ($request->filled('password')) {
            $validator->addRules([
                'password' => 'required|min:6',
                'confirm_password' => 'required|same:password'
            ]);
        }

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
        $client->phone2 = $request->phone2;
        $client->address_line1 = $request->address_line1;
        $client->address_line2 = $request->address_line2;
        $client->address_line3 = $request->address_line3;
        $client->trading_address = $request->trading_address;
        $client->city = $request->city;
        $client->town = $request->town;
        $client->postcode = $request->postcode;
        $client->country = $request->country;
        if ($request->filled('password')) {
            $client->password = Hash::make($request->password);
        }
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
            'nature_of_business' => 'nullable|string',
            'company_number' => 'required|string',
            'year_end_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'confirmation_due_date' => 'nullable|date',
            'authorization_code' => 'required|string',
            'company_utr' => 'required|string',
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
                'ct_authorization' => $request->ct_authorization,
                'vat_number' => $request->vat_number,
                'status' => $request->status,
                'paye_ref_number' => $request->paye_ref_number,
                'paye_authorization' => $request->paye_authorization,
                'account_office_ref_number' => $request->account_office_ref_number,
                'vat_authorization' => $request->vat_authorization,
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
        $director->directors_tax_return = $request->directors_tax_return;
        $director->hmrc_authorisation = $request->hmrc_authorisation;
        $director->updated_by = Auth::id();

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
        $contactInfo->company = $request->company;
        $contactInfo->updated_by = Auth::id();

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

    public function clientReport($id)
    {
        $client = Client::with(['clientType', 'manager', 'businessInfo', 'directorInfos', 'clientServices.clientSubServices', 'contactInfos', 'recentUpdates.user', 'accountancyFee'])->find($id);
        return view('admin.client.report', compact('client'));
    }

    public function clientAboutBusinessUpdate(Request $request)
    {
        $request->validate([
            'about_business' => 'nullable|string',
            'id' => 'required',
        ]);

        $client = Client::findOrFail($request->id);
        $client->about_business = $request->about_business;
        $client->save();

        return response()->json([
            'message' => 'Updated successfully!',
        ]);
    }

    public function clientAccountancyFee(Request $request)
    {
        $request->validate([
            'client_id' => 'required',
            'annual_agreed_fees' => 'required|numeric',
            'monthly_standing_order' => 'required|boolean',
            'monthly_amount' => 'required|numeric',
            'next_review' => 'nullable|date',
            'comment' => 'nullable|string',
            'fees_discussion' => 'nullable|string',
        ]);

        $client = Client::findOrFail($request->client_id);

        $accountancyFee = $client->accountancyFee;

        if ($accountancyFee) {
            $accountancyFee->update([
                'annual_agreed_fees' => $request->annual_agreed_fees,
                'monthly_standing_order' => $request->monthly_standing_order,
                'monthly_amount' => $request->monthly_amount,
                'next_review' => $request->next_review,
                'comment' => $request->comment,
                'fees_discussion' => $request->fees_discussion,
            ]);
        } else {
            $client->accountancyFee()->create([
                'annual_agreed_fees' => $request->annual_agreed_fees,
                'monthly_standing_order' => $request->monthly_standing_order,
                'monthly_amount' => $request->monthly_amount,
                'next_review' => $request->next_review,
                'comment' => $request->comment,
                'fees_discussion' => $request->fees_discussion,
            ]);
        }

        return response()->json(['message' => 'Accountancy fee updated successfully']);
    }


}
