<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountHead;
use App\Models\AccountType;
use App\Models\TaxRate;
use Illuminate\Http\Request;
use DataTables;

class AccountHeadController extends Controller
{
    public function index()
    {
        $accountTypes = AccountType::where('is_active', 1)->select('id', 'name')->get();
        $taxRates     = TaxRate::where('is_active', 1)->select('id', 'name', 'rate')->get();
        return view('admin.account_head.index', compact('accountTypes', 'taxRates'));
    }

    public function datatable()
    {
    $query = AccountHead::with('accountType:id,name', 'taxRate:id,name,rate', 'clientCredential:id,first_name,last_name')
        ->select('account_heads.*')
        ->orderBy('code')
        ->when(request('category'), function($q) {
                $q->whereHas('accountType', fn($q2) => $q2->where('category', request('category')));
            })
        ->when(request('client_credential_id'), function($q) {
                $credId = request('client_credential_id');
                if (is_array($credId)) $credId = reset($credId);
                $q->where('client_credential_id', $credId);
            });

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('account_type_name', fn($row) => $row->accountType?->name)
            ->addColumn('tax_rate_name', fn($row) => $row->taxRate ? $row->taxRate->name.' ('.$row->taxRate->rate.'%)' : '-')
            ->addColumn('client_name', fn($row) => $row->clientCredential ? trim($row->clientCredential->first_name . ' ' . $row->clientCredential->last_name) : '<span class="text-muted">Global</span>')
            ->addColumn('status', function($row){
                $checked = $row->is_active ? 'checked' : '';
                return '<label class="switch">
                    <input type="checkbox" class="toggleStatus" data-id="'.$row->id.'" '.$checked.'>
                    <span class="slider round"></span>
                </label>';
            })
            ->addColumn('action', function($row){
                return '<a class="btn btn-link EditBtn" rid="'.$row->id.'">
                            <i class="fa fa-edit" style="font-size:20px"></i>
                        </a>
                        <a class="btn btn-link deleteBtn" rid="'.$row->id.'">
                            <i class="fa fa-trash" style="font-size:20px;color:red"></i>
                        </a>';
            })
            ->rawColumns(['status', 'action', 'client_name'])
            ->make(true);
    }

    public function checkCode(Request $request)
    {
        $credId = $request->client_credential_id ?: null;
        $exists = AccountHead::where('code', $request->code)
            ->where('client_credential_id', $credId)
            ->when($request->id, fn($q) => $q->where('id', '!=', $request->id))
            ->exists();
        return response()->json(['available' => !$exists]);
    }

    public function store(Request $request)
    {
        if(empty($request->account_type_id)){
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>Please select Account Type.</div>"]);
        }
        if(empty($request->code)){
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>Please fill Code field.</div>"]);
        }
        if(empty($request->name)){
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>Please fill Account Head field.</div>"]);
        }

        $credId = $request->client_credential_id ?: null;

        if(AccountHead::where('code', $request->code)->where('client_credential_id', $credId)->exists()){
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>This code already exists for this client scope.</div>"]);
        }
        if(AccountHead::where('name', $request->name)->where('client_credential_id', $credId)->exists()){
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>This account head already exists for this client scope.</div>"]);
        }

        AccountHead::create([
            'account_type_id'        => $request->account_type_id,
            'tax_rate_id'            => $request->tax_rate_id ?: null,
            'client_credential_id'   => $request->client_credential_id ?: null,
            'code'                   => $request->code,
            'name'                   => $request->name,
            'description'            => $request->description,
            'is_active'              => 1,
        ]);
        return response()->json(['status'=>300,'message'=>'Account head created successfully.']);
    }

    public function byType($typeId)
    {
        $heads = AccountHead::where('account_type_id', $typeId)
            ->where('is_active', 1)
            ->where(function ($q) {
                $q->whereNull('client_credential_id');
                if (request('client_credential_id')) {
                    $q->orWhere('client_credential_id', request('client_credential_id'));
                }
            })
            ->select('id', 'name', 'tax_rate_id', 'code')
            ->with('taxRate:id,rate')
            ->orderBy('code')
            ->get()
            ->map(fn($h) => [
                'id'       => $h->id,
                'name'     => $h->name,
                'tax_rate' => $h->taxRate?->rate ?? 0,
            ]);

        return response()->json($heads);
    }

    public function edit($id)
    {
        return response()->json(AccountHead::find($id));
    }

    public function update(Request $request)
    {
        if(empty($request->account_type_id)){
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>Please select Account Type.</div>"]);
        }
        if(empty($request->code)){
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>Please fill Code field.</div>"]);
        }
        if(empty($request->name)){
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>Please fill Account Head field.</div>"]);
        }

        $credId = $request->client_credential_id ?: null;

        if(AccountHead::where('code', $request->code)->where('client_credential_id', $credId)->where('id','!=',$request->codeid)->exists()){
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>This code already exists for this client scope.</div>"]);
        }
        if(AccountHead::where('name', $request->name)->where('client_credential_id', $credId)->where('id','!=',$request->codeid)->exists()){
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>This account head already exists for this client scope.</div>"]);
        }

        AccountHead::find($request->codeid)->update([
            'account_type_id'        => $request->account_type_id,
            'tax_rate_id'            => $request->tax_rate_id ?: null,
            'client_credential_id'   => $request->client_credential_id ?: null,
            'code'                   => $request->code,
            'name'                   => $request->name,
            'description'            => $request->description,
        ]);
        return response()->json(['status'=>300,'message'=>'Account head updated successfully.']);
    }

    public function delete($id)
    {
        AccountHead::destroy($id);
        return response()->json(['success'=>true,'message'=>'Deleted successfully.']);
    }

    public function toggleStatus($id)
    {
        $head = AccountHead::find($id);
        $head->is_active = !$head->is_active;
        $head->save();
        return response()->json(['success'=>true]);
    }
}