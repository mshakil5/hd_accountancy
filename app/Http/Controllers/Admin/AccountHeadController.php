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
        $query = AccountHead::with('accountType:id,name', 'taxRate:id,name,rate')
            ->select('account_heads.*');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('account_type_name', fn($row) => $row->accountType?->name)
            ->addColumn('tax_rate_name', fn($row) => $row->taxRate ? $row->taxRate->name.' ('.$row->taxRate->rate.'%)' : '-')
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
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function checkCode(Request $request)
    {
        $exists = AccountHead::where('code', $request->code)
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
        if(AccountHead::where('code', $request->code)->exists()){
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>This code already exists.</div>"]);
        }
        if(empty($request->name)){
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>Please fill Account Head field.</div>"]);
        }
        if(AccountHead::where('name', $request->name)->exists()){
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>This account head already exists.</div>"]);
        }

        AccountHead::create([
            'account_type_id' => $request->account_type_id,
            'tax_rate_id'     => $request->tax_rate_id ?: null,
            'code'            => $request->code,
            'name'            => $request->name,
            'description'     => $request->description,
            'is_active'       => 1,
        ]);
        return response()->json(['status'=>300,'message'=>'Account head created successfully.']);
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
        if(AccountHead::where('code', $request->code)->where('id','!=',$request->codeid)->exists()){
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>This code already exists.</div>"]);
        }
        if(empty($request->name)){
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>Please fill Account Head field.</div>"]);
        }
        if(AccountHead::where('name', $request->name)->where('id','!=',$request->codeid)->exists()){
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>This account head already exists.</div>"]);
        }

        AccountHead::find($request->codeid)->update([
            'account_type_id' => $request->account_type_id,
            'tax_rate_id'     => $request->tax_rate_id ?: null,
            'code'            => $request->code,
            'name'            => $request->name,
            'description'     => $request->description,
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