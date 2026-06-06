<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TaxRate;
use Illuminate\Http\Request;
use DataTables;

class TaxRateController extends Controller
{
    public function index()
    {
        return view('admin.tax_rate.index');
    }

    public function datatable()
    {
        $query = TaxRate::select('id', 'name', 'rate', 'is_active');
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
        if($request->rate === null || $request->rate === ''){
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>Please fill Rate field.</div>"]);
        }
        if(TaxRate::where('name', $request->name)->exists()){
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>This tax rate name already exists.</div>"]);
        }

        TaxRate::create(['name' => $request->name, 'rate' => $request->rate, 'is_active' => 1]);
        return response()->json(['status'=>300,'message'=>'Tax rate created successfully.']);
    }

    public function edit($id)
    {
        return response()->json(TaxRate::find($id));
    }

    public function update(Request $request)
    {
        if(empty($request->name)){
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>Please fill Name field.</div>"]);
        }
        if($request->rate === null || $request->rate === ''){
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>Please fill Rate field.</div>"]);
        }
        if(TaxRate::where('name', $request->name)->where('id','!=',$request->codeid)->exists()){
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>This tax rate name already exists.</div>"]);
        }

        TaxRate::find($request->codeid)->update(['name' => $request->name, 'rate' => $request->rate]);
        return response()->json(['status'=>300,'message'=>'Tax rate updated successfully.']);
    }

    public function delete($id)
    {
        TaxRate::destroy($id);
        return response()->json(['success'=>true,'message'=>'Deleted successfully.']);
    }

    public function toggleStatus($id)
    {
        $tax = TaxRate::find($id);
        $tax->is_active = !$tax->is_active;
        $tax->save();
        return response()->json(['success'=>true]);
    }
}