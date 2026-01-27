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
use App\Models\ClientCredential;
use Illuminate\Support\Facades\Hash;
use App\Models\ClientProperty;

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
            $query = Client::with(['clientType', 'manager', 'clientSubServices', 'directorInfos'])->latest();
        
            if ($filter == 'assigned') {
                $query->whereHas('clientSubServices', function ($subQuery) use ($authUserId) {
                    $subQuery->where('staff_id', $authUserId);
                })
                ->orWhereHas('clientServices', function ($subQuery) use ($authUserId) {
                    $subQuery->where('manager_id', $authUserId);
                })
                ->orWhere('manager_id', $authUserId);
            }

            // Handle global search
            if ($request->has('search') && !empty($request->search['value'])) {
                $searchValue = $request->search['value'];
                
                $query->where(function($q) use ($searchValue) {
                    $q->where('name', 'like', "%{$searchValue}%")
                    ->orWhere('email', 'like', "%{$searchValue}%")
                    ->orWhere('phone', 'like', "%{$searchValue}%")
                    ->orWhere('refid', 'like', "%{$searchValue}%")
                    ->orWhereHas('directorInfos', function($subQuery) use ($searchValue) {
                        $subQuery->where('name', 'like', "%{$searchValue}%")
                                ->orWhere('last_name', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('manager', function($subQuery) use ($searchValue) {
                        $subQuery->where('first_name', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('clientType', function($subQuery) use ($searchValue) {
                        $subQuery->where('name', 'like', "%{$searchValue}%");
                    });
                });
            }

            // Handle column-specific searches if needed
            if ($request->has('columns')) {
                foreach ($request->columns as $column) {
                    if ($column['searchable'] == 'true' && !empty($column['search']['value'])) {
                        $searchValue = $column['search']['value'];
                        
                        switch ($column['data']) {
                            case 'directors':
                                $query->whereHas('directorInfos', function($subQuery) use ($searchValue) {
                                    $subQuery->where('name', 'like', "%{$searchValue}%")
                                            ->orWhere('last_name', 'like', "%{$searchValue}%");
                                });
                                break;
                            // Add other column-specific searches as needed
                        }
                    }
                }
            }
        
            // Get total count before pagination
            $totalRecords = $query->count();
            
            // Apply pagination
            $start = $request->input('start', 0);
            $length = $request->input('length', 10);
            $query->skip($start)->take($length);
            
            // Apply ordering
            if ($request->has('order')) {
                $orderColumnIndex = $request->input('order.0.column');
                $orderDirection = $request->input('order.0.dir');
                
                // Map column index to column name
                $columns = ['id', 'refid', 'name', 'manager_first_name', 'phone', 'email', 'client_type_name', 'directors', 'status'];
                
                if (isset($columns[$orderColumnIndex])) {
                    $orderColumn = $columns[$orderColumnIndex];
                    
                    // Handle special ordering for relations
                    if ($orderColumn == 'manager_first_name') {
                        $query->leftJoin('users', 'clients.manager_id', '=', 'users.id')
                            ->orderBy('users.first_name', $orderDirection);
                    } elseif ($orderColumn == 'client_type_name') {
                        $query->leftJoin('client_types', 'clients.client_type_id', '=', 'client_types.id')
                            ->orderBy('client_types.name', $orderDirection);
                    } elseif ($orderColumn == 'directors') {
                        // For directors, order by first director's name
                        $query->leftJoin('director_infos', 'clients.id', '=', 'director_infos.client_id')
                            ->orderBy('director_infos.name', $orderDirection)
                            ->groupBy('clients.id');
                    } else {
                        $query->orderBy('clients.' . $orderColumn, $orderDirection);
                    }
                }
            } else {
                $query->orderBy('id', 'desc');
            }
            
            $data = $query->get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('manager_first_name', function ($row) {
                    return $row->manager->first_name ?? '';
                })
                ->addColumn('client_type_name', function ($row) {
                    return $row->clientType->name ?? '';
                })
                ->addColumn('directors', function ($row) {
                    $directors = $row->directorInfos;
                    if ($directors->isEmpty()) {
                        return '<span class="text-muted">No directors</span>';
                    }
                    
                    $directorNames = $directors->map(function($director) {
                        return $director->name . ' ' . $director->last_name;
                    })->implode(', ');
                    
                    return '<span class="director-names">' . $directorNames . '</span>';
                })
                ->with([
                    'draw' => $request->input('draw'),
                    'recordsTotal' => Client::count(),
                    'recordsFiltered' => $totalRecords,
                ])
                ->rawColumns(['manager_first_name', 'client_type_name', 'directors'])
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
        $clientCridentials = ClientCredential::where('status',1)->get();
        return view('admin.client.create', compact('clientTypes', 'managers', 'client','clientCridentials'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_credential_id' => 'required|string|max:255',
            'client_type_id' => 'required',
            'reference_id' => 'required',
            'manager_id' => 'nullable',
            'client_reference' => 'required|string|max:255',
            'email' => 'required|email',
            'secondary_email' => 'nullable|email',
            'phone' => 'required|numeric',
            'phone2' => 'nullable|numeric',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'postcode' => 'nullable|string',
            'agreement_date' => 'nullable|date',
            'cessation_date' => 'nullable|date',
            'name' => 'nullable|string|max:255',
            'dob' => 'nullable|date',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'photo_id_saved' => 'nullable|string|in:Y,N',
            'hmrc_authorization' => 'nullable|string|in:Y,N',
            'utr_number' => 'nullable|string|max:255',
            'ni_number' => 'nullable|string|max:255',
            'business_name' => 'nullable|string|max:255',
            'type_of_business' => 'nullable|string|max:255',
            'number_of_property' => 'nullable|numeric',
            'property_address' => 'nullable|string|max:500',
            'company_name' => 'nullable|string|max:255',
            'company_number' => 'nullable|string|max:255',
            'registered_address_line1' => 'nullable|string|max:255',
            'registered_address_line2' => 'nullable|string|max:255',
            'trading_address_line1' => 'nullable|string|max:255',
            'trading_address_line2' => 'nullable|string|max:255',
            'partnership_business_name' => 'nullable|string|max:255',
            'partnership_trading_address_line1' => 'nullable|string|max:255',
            'partnership_trading_address_line2' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $clientType = ClientType::find($request->client_type_id);
        $clientTypeNameLower = strtolower($clientType->name ?? '');

        $data = new Client;
        $data->client_credential_id = $request->client_credential_id;
        $data->refid = $request->reference_id;
        $data->client_type_id = $request->client_type_id;
        $data->manager_id = $request->manager_id;
        $data->email = $request->email;
        $data->secondary_email = $request->secondary_email;
        $data->phone = $request->phone;
        $data->phone2 = $request->phone2;
        $data->city = $request->city;
        $data->country = $request->country;
        $data->postcode = $request->postcode;
        $data->agreement_date = $request->agreement_date;
        $data->cessation_date = $request->cessation_date;
        $data->created_by = Auth::id();

        if ($clientTypeNameLower === 'sole trader') {
            $data->name = $request->name;
            $data->dob = $request->dob;
            $data->address_line1 = $request->address_line1;
            $data->address_line2 = $request->address_line2;
            $data->business_name = $request->business_name;
            $data->photo_id_saved = $request->photo_id_saved;
            $data->hmrc_authorization = $request->hmrc_authorization;
            $data->utr_number = $request->utr_number;
            $data->ni_number = $request->ni_number;
        } else if ($clientTypeNameLower === 'self assesment') {
            $data->name = $request->name;
            $data->dob = $request->dob;
            $data->address_line1 = $request->address_line1;
            $data->address_line2 = $request->address_line2;
            $data->type_of_business = $request->type_of_business;
            $data->photo_id_saved = $request->photo_id_saved;
            $data->hmrc_authorization = $request->hmrc_authorization;
            $data->utr_number = $request->utr_number;
            $data->ni_number = $request->ni_number;
        } else if ($clientTypeNameLower === 'landlord') {
            $data->name = $request->name;
            $data->dob = $request->dob;
            $data->address_line1 = $request->address_line1;
            $data->address_line2 = $request->address_line2;
            $data->number_of_property = $request->number_of_property;
            $data->property_address = $request->property_address;
            $data->photo_id_saved = $request->photo_id_saved;
            $data->hmrc_authorization = $request->hmrc_authorization;
            $data->utr_number = $request->utr_number;
            $data->ni_number = $request->ni_number;
        } else if ($clientTypeNameLower === 'limited company') {
            $data->company_name = $request->company_name;
            $data->company_number = $request->company_number;
            $data->registered_address_line1 = $request->registered_address_line1;
            $data->registered_address_line2 = $request->registered_address_line2;
            $data->trading_address_line1 = $request->trading_address_line1;
            $data->trading_address_line2 = $request->trading_address_line2;
        } else if ($clientTypeNameLower === 'partnership') {
            $data->partnership_business_name = $request->partnership_business_name;
            $data->partnership_trading_address_line1 = $request->partnership_trading_address_line1;
            $data->partnership_trading_address_line2 = $request->partnership_trading_address_line2;
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
        $client = Client::with(['clientType', 'manager', 'businessInfo', 'directorInfos', 'clientServices', 'contactInfos', 'recentUpdates.user', 'accountancyFee', 'properties'])->find($id);
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
            'client_type_id' => 'required',
            'manager_id' => 'nullable',
            'reference_id' => 'required',
            'photo' => 'nullable|mimes:jpeg,png,jpg,gif,svg|max:8048'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }
        
        $client = Client::find($id);

        if (!$client) {
            return response()->json(['status' => 404, 'message' => 'Client not found'], 404);
        }

        $client->refid = $request->reference_id;
        $client->client_type_id = $request->client_type_id;
        $client->manager_id = $request->manager_id;
        $client->updated_by = Auth::id();

        $clientType = strtolower($client->clientType->name ?? '');

        if ($clientType === 'sole trader') {
            if ($request->st_name) $client->name = $request->st_name;
            if ($request->st_dob) $client->dob = $request->st_dob;
            if ($request->st_email) $client->email = $request->st_email;
            if ($request->st_secondary_email) $client->secondary_email = $request->st_secondary_email;
            if ($request->st_phone) $client->phone = $request->st_phone;
            if ($request->st_phone2) $client->phone2 = $request->st_phone2;
            if ($request->st_address_line1) $client->address_line1 = $request->st_address_line1;
            if ($request->st_address_line2) $client->address_line2 = $request->st_address_line2;
            if ($request->city) $client->city = $request->city;
            if ($request->country) $client->country = $request->country;
            if ($request->postcode) $client->postcode = $request->postcode;
            if ($request->st_business_name) $client->business_name = $request->st_business_name;
            if ($request->st_photo_id_saved) $client->photo_id_saved = $request->st_photo_id_saved;
            if ($request->st_hmrc_authorization) $client->hmrc_authorization = $request->st_hmrc_authorization;
            if ($request->st_utr_number) $client->utr_number = $request->st_utr_number;
            if ($request->st_ni_number) $client->ni_number = $request->st_ni_number;
            if ($request->st_agreement_date) $client->agreement_date = $request->st_agreement_date;
            if ($request->st_cessation_date) $client->cessation_date = $request->st_cessation_date;
        } 
        elseif ($clientType === 'self assesment') {
            if ($request->sa_name) $client->name = $request->sa_name;
            if ($request->sa_dob) $client->dob = $request->sa_dob;
            if ($request->sa_email) $client->email = $request->sa_email;
            if ($request->sa_secondary_email) $client->secondary_email = $request->sa_secondary_email;
            if ($request->sa_phone) $client->phone = $request->sa_phone;
            if ($request->sa_phone2) $client->phone2 = $request->sa_phone2;
            if ($request->sa_address_line1) $client->address_line1 = $request->sa_address_line1;
            if ($request->sa_address_line2) $client->address_line2 = $request->sa_address_line2;
            if ($request->city) $client->city = $request->city;
            if ($request->country) $client->country = $request->country;
            if ($request->postcode) $client->postcode = $request->postcode;
            if ($request->sa_type_of_business) $client->type_of_business = $request->sa_type_of_business;
            if ($request->sa_photo_id_saved) $client->photo_id_saved = $request->sa_photo_id_saved;
            if ($request->sa_hmrc_authorization) $client->hmrc_authorization = $request->sa_hmrc_authorization;
            if ($request->sa_utr_number) $client->utr_number = $request->sa_utr_number;
            if ($request->sa_ni_number) $client->ni_number = $request->sa_ni_number;
            if ($request->sa_agreement_date) $client->agreement_date = $request->sa_agreement_date;
            if ($request->sa_cessation_date) $client->cessation_date = $request->sa_cessation_date;
        } 
        elseif ($clientType === 'landlord') {
            if ($request->ll_name) $client->name = $request->ll_name;
            if ($request->ll_dob) $client->dob = $request->ll_dob;
            if ($request->ll_email) $client->email = $request->ll_email;
            if ($request->ll_secondary_email) $client->secondary_email = $request->ll_secondary_email;
            if ($request->ll_phone) $client->phone = $request->ll_phone;
            if ($request->ll_phone2) $client->phone2 = $request->ll_phone2;
            if ($request->ll_address_line1) $client->address_line1 = $request->ll_address_line1;
            if ($request->ll_address_line2) $client->address_line2 = $request->ll_address_line2;
            if ($request->city) $client->city = $request->city;
            if ($request->country) $client->country = $request->country;
            if ($request->postcode) $client->postcode = $request->postcode;
            if ($request->ll_photo_id_saved) $client->photo_id_saved = $request->ll_photo_id_saved;
            if ($request->ll_hmrc_authorization) $client->hmrc_authorization = $request->ll_hmrc_authorization;
            if ($request->ll_utr_number) $client->utr_number = $request->ll_utr_number;
            if ($request->ll_ni_number) $client->ni_number = $request->ll_ni_number;
            if ($request->ll_agreement_date) $client->agreement_date = $request->ll_agreement_date;
            if ($request->ll_cessation_date) $client->cessation_date = $request->ll_cessation_date;
        } 
        elseif ($clientType === 'limited company' || $clientType === 'vat registered company') {
            if ($request->lc_company_name) $client->company_name = $request->lc_company_name;
            if ($request->lc_company_number) $client->company_number = $request->lc_company_number;
            if ($request->lc_registered_address_line1) $client->registered_address_line1 = $request->lc_registered_address_line1;
            if ($request->lc_registered_address_line2) $client->registered_address_line2 = $request->lc_registered_address_line2;
            if ($request->lc_trading_address_line1) $client->trading_address_line1 = $request->lc_trading_address_line1;
            if ($request->lc_trading_address_line2) $client->trading_address_line2 = $request->lc_trading_address_line2;
            if ($request->lc_email) $client->email = $request->lc_email;
            if ($request->lc_secondary_email) $client->secondary_email = $request->lc_secondary_email;
            if ($request->lc_phone) $client->phone = $request->lc_phone;
            if ($request->lc_phone2) $client->phone2 = $request->lc_phone2;
            if ($request->city) $client->city = $request->city;
            if ($request->country) $client->country = $request->country;
            if ($request->postcode) $client->postcode = $request->postcode;
            if ($request->lc_agreement_date) $client->agreement_date = $request->lc_agreement_date;
            if ($request->lc_cessation_date) $client->cessation_date = $request->lc_cessation_date;
        } 
        elseif ($clientType === 'partnership') {
            if ($request->p_business_name) $client->partnership_business_name = $request->p_business_name;
            if ($request->p_email) $client->email = $request->p_email;
            if ($request->p_secondary_email) $client->secondary_email = $request->p_secondary_email;
            if ($request->p_phone) $client->phone = $request->p_phone;
            if ($request->p_phone2) $client->phone2 = $request->p_phone2;
            if ($request->p_trading_address_line1) $client->partnership_trading_address_line1 = $request->p_trading_address_line1;
            if ($request->p_trading_address_line2) $client->partnership_trading_address_line2 = $request->p_trading_address_line2;
            if ($request->city) $client->city = $request->city;
            if ($request->country) $client->country = $request->country;
            if ($request->postcode) $client->postcode = $request->postcode;
            if ($request->p_agreement_date) $client->agreement_date = $request->p_agreement_date;
            if ($request->p_cessation_date) $client->cessation_date = $request->p_cessation_date;
        }

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

        $properties = $request->input('properties');

        if (is_string($properties)) {
            $properties = json_decode($properties, true) ?: [];
        }

        if (!is_array($properties)) {
            $properties = [];
        }

        $existingIds = [];

        foreach ($properties as $propertyData) {
            if (!empty($propertyData['address'])) {
                $property = ClientProperty::updateOrCreate(
                    ['id' => $propertyData['id'] ?? null],
                    [
                        'client_id' => $client->id,
                        'address' => $propertyData['address'],
                    ]
                );
                $existingIds[] = $property->id;
            }
        }

        ClientProperty::where('client_id', $client->id)
            ->whereNotIn('id', $existingIds)
            ->delete();

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
            'company_number' => 'nullable|string',
            'year_end_date' => 'nullable',
            'due_date' => 'nullable',
            'confirmation_due_date' => 'nullable',
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
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $director = DirectorInfo::findOrFail($id);

        $director->name = $request->name;
        $director->last_name = $request->last_name;
        $director->phone = $request->phone;
        $director->email = $request->email;
        $director->address = $request->address;
        $director->address_line_2 = $request->address_line_2;
        $director->city = $request->city;
        $director->country = $request->country;
        $director->post_code = $request->post_code;
        $data->photo_id_saved = $request->photo_id_saved ?? null;
        $director->dob = $request->dob;
        $director->ni_number = $request->ni_number;
        $director->directors_tax_return = $request->directors_tax_return ?: 0;
        $director->dir_verification_code = $request->dir_verification_code;
        $director->hmrc_authorisation = $request->hmrc_authorisation ?: 0;
        $director->utr_number = $request->utr_number;
        $director->utr_authorization = $request->utr_authorization ?: 0;
        $director->nino = $request->nino;
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
            'annual_agreed_fees' => 'nullable|numeric',
            'monthly_standing_order' => 'required|boolean',
            'monthly_amount' => 'nullable|numeric',
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

    public function getClientInfo($id)
    {
        $client = ClientCredential::find($id);

        if (!$client) {
            return response()->json(['error' => 'Client not found'], 404);
        }

        return response()->json([
            'first_name' => $client->first_name,
            'last_name'  => $client->last_name,
            'email'      => $client->email,
            'phone'      => $client->phone,
        ]);
    }



}
