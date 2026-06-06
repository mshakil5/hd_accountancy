<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountType;
use Illuminate\Http\Request;
use DataTables;

class AccountTypeController extends Controller
{
    public function index()
    {
        return view('admin.account_type.index');
    }

    public function datatable()
    {
        $query = AccountType::select('id', 'name', 'category', 'normal_balance', 'is_active');
        return DataTables::of($query)
            ->addIndexColumn()
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

    public function store(Request $request)
    {
        if(empty($request->name)){
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>Please fill Name field.</div>"]);
        }
        if(empty($request->category)){
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>Please select Category.</div>"]);
        }
        if(empty($request->normal_balance)){
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>Please select Normal Balance.</div>"]);
        }
        if(AccountType::where('name', $request->name)->exists()){
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>This account type already exists.</div>"]);
        }

        AccountType::create([
            'name'           => $request->name,
            'category'       => $request->category,
            'normal_balance' => $request->normal_balance,
            'is_active'      => 1,
        ]);
        return response()->json(['status'=>300,'message'=>'Account type created successfully.']);
    }

    public function edit($id)
    {
        return response()->json(AccountType::find($id));
    }

    public function update(Request $request)
    {
        if(empty($request->name)){
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>Please fill Name field.</div>"]);
        }
        if(empty($request->category)){
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>Please select Category.</div>"]);
        }
        if(empty($request->normal_balance)){
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>Please select Normal Balance.</div>"]);
        }
        if(AccountType::where('name', $request->name)->where('id','!=',$request->codeid)->exists()){
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>This account type already exists.</div>"]);
        }

        AccountType::find($request->codeid)->update([
            'name'           => $request->name,
            'category'       => $request->category,
            'normal_balance' => $request->normal_balance,
        ]);
        return response()->json(['status'=>300,'message'=>'Account type updated successfully.']);
    }

    public function delete($id)
    {
        AccountType::destroy($id);
        return response()->json(['success'=>true,'message'=>'Deleted successfully.']);
    }

    public function toggleStatus($id)
    {
        $type = AccountType::find($id);
        $type->is_active = !$type->is_active;
        $type->save();
        return response()->json(['success'=>true]);
    }
}