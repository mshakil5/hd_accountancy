<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountHead;
use App\Models\AccountType;
use Illuminate\Http\Request;
use DataTables;

class AccountTypeController extends Controller
{
    public function index()
    {
        // Head-first: the type form starts by picking the Account Head it lives under.
        $heads = AccountHead::with('clientCredential:id,first_name,last_name')
            ->where('is_active', 1)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'client_credential_id']);
        return view('admin.account_type.index', compact('heads'));
    }

    public function datatable()
    {
        $query = AccountType::with('accountHead:id,code,name', 'clientCredential:id,first_name,last_name')
            ->select('account_types.*');
        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('account_head_name', function($row){
                if ($row->accountHead) {
                    return $row->accountHead->code . ' &mdash; ' . $row->accountHead->name;
                }
                return '<span class="text-muted">Shared (no head)</span>';
            })
            ->addColumn('client_name', function($row){
                return $row->clientCredential
                    ? trim($row->clientCredential->first_name . ' ' . $row->clientCredential->last_name)
                    : '<span class="text-muted">Global</span>';
            })
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
            ->rawColumns(['status', 'action', 'client_name', 'account_head_name'])
            ->make(true);
    }

    /**
     * Scoped name uniqueness: same-scope duplicates are blocked, and a
     * client-scoped type may not shadow a global (shared) type's name.
     */
    private function nameTaken(string $name, ?int $credId, ?int $exceptId = null): ?AccountType
    {
        return AccountType::where('name', $name)
            ->when($credId, function ($q) use ($credId) {
                $q->where(fn ($qq) => $qq->where('client_credential_id', $credId)->orWhereNull('client_credential_id'));
            }, function ($q) {
                $q->whereNull('client_credential_id');
            })
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->first();
    }

    public function store(Request $request)
    {
        // Head-first: the Account Head is picked before the type details.
        if(empty($request->account_head_id)){
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>Please select the Account Head this type belongs to.</div>"]);
        }
        $head = AccountHead::find($request->account_head_id);
        if(!$head){
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>Selected Account Head not found.</div>"]);
        }
        if(empty($request->name)){
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>Please fill Name field.</div>"]);
        }
        if(empty($request->category)){
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>Please select Category.</div>"]);
        }
        if(empty($request->normal_balance)){
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>Please select Normal Balance.</div>"]);
        }

        // A type's scope follows the head it is made under:
        // global head -> global type, per-client head -> that client's type.
        $credId = $head->client_credential_id ?: null;

        $taken = $this->nameTaken($request->name, $credId);
        if($taken){
            $hint = ($credId && !$taken->client_credential_id)
                ? " This name is already used by a global (shared) account type."
                : "";
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>This account type already exists.{$hint}</div>"]);
        }

        $type = AccountType::create([
            'name'                => $request->name,
            'category'            => $request->category,
            'normal_balance'      => $request->normal_balance,
            'account_head_id'     => $head->id,
            'client_credential_id'=> $credId,
            'is_active'           => 1,
        ]);

        // Head-first flow: a brand-new type made under an untyped head
        // becomes that head's type automatically.
        if(!$head->account_type_id){
            $head->update(['account_type_id' => $type->id]);
        }

        return response()->json(['status'=>300,'message'=>'Account type created successfully.','id'=>$type->id]);
    }

    public function edit($id)
    {
        return response()->json(AccountType::find($id));
    }

    public function update(Request $request)
    {
        $type = AccountType::find($request->codeid);
        if(!$type){
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>Account type not found.</div>"]);
        }
        if(empty($request->name)){
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>Please fill Name field.</div>"]);
        }
        if(empty($request->category)){
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>Please select Category.</div>"]);
        }
        if(empty($request->normal_balance)){
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>Please select Normal Balance.</div>"]);
        }

        // Absent field keeps the current head; explicit empty unlinks it
        // (legacy shared types stay shared).
        $headId = $request->has('account_head_id') ? ($request->account_head_id ?: null) : $type->account_head_id;
        $head = $headId ? AccountHead::find($headId) : null;
        if($headId && !$head){
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>Selected Account Head not found.</div>"]);
        }
        $credId = $head ? ($head->client_credential_id ?: null) : null;

        $taken = $this->nameTaken($request->name, $credId, (int) $type->id);
        if($taken){
            return response()->json(['status'=>303,'message'=>"<div class='alert alert-warning'>This account type already exists.</div>"]);
        }

        $type->update([
            'name'                => $request->name,
            'category'            => $request->category,
            'normal_balance'      => $request->normal_balance,
            'account_head_id'     => $headId,
            'client_credential_id'=> $credId,
        ]);

        // Moving a type underneath a different head keeps scopes in sync.
        if($headId){
            AccountType::where('account_head_id', $headId)
                ->where('id', '!=', $type->id)
                ->update(['client_credential_id' => $credId]);
        }
        // An untyped head adopts the type moved under it.
        if($head && !$head->account_type_id){
            $head->update(['account_type_id' => $type->id]);
        }

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
