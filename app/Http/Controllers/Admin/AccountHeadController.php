<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountHead;
use App\Models\AccountSubType;
use App\Models\AccountType;
use Illuminate\Http\Request;
use DataTables;

class AccountHeadController extends Controller
{
    public function index()
    {
        $accountTypes = AccountType::where('is_active', 1)->orderBy('id')->get(['id', 'name']);
        return view('admin.account_head.index', compact('accountTypes'));
    }

    public function datatable()
    {
        $query = AccountHead::with('accountType:id,name', 'accountSubType:id,name', 'clientCredential:id,first_name,last_name')
            ->select('account_heads.*')
            ->orderBy('code')
            ->when(request('account_type_id'), function ($q) {
                $q->where('account_type_id', request('account_type_id'));
            })
            ->when(request('client_credential_id'), function ($q) {
                $credId = request('client_credential_id');
                if (is_array($credId)) $credId = reset($credId);
                $q->where('client_credential_id', $credId);
            });

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('type_name', fn($row) => $row->accountType?->name ?? '-')
            ->addColumn('sub_type_name', fn($row) => $row->accountSubType?->name ?? '-')
            ->addColumn('client_name', fn($row) => $row->clientCredential ? trim($row->clientCredential->first_name . ' ' . $row->clientCredential->last_name) : '<span class="text-muted">Global</span>')
            ->addColumn('normal_balance_label', function ($row) {
                return $row->normalBalance() === 'debit'
                    ? '<span class="badge bg-danger">Debit</span>'
                    : '<span class="badge bg-success">Credit</span>';
            })
            ->addColumn('status', function ($row) {
                $checked = $row->is_active ? 'checked' : '';
                return '<label class="switch">
                    <input type="checkbox" class="toggleStatus" data-id="'.$row->id.'" '.$checked.'>
                    <span class="slider round"></span>
                </label>';
            })
            ->addColumn('action', function ($row) {
                return '<a class="btn btn-link EditBtn" rid="'.$row->id.'">
                            <i class="fa fa-edit" style="font-size:20px"></i>
                        </a>
                        <a class="btn btn-link deleteBtn" rid="'.$row->id.'">
                            <i class="fa fa-trash" style="font-size:20px;color:red"></i>
                        </a>';
            })
            ->rawColumns(['status', 'action', 'client_name', 'normal_balance_label'])
            ->make(true);
    }

    /**
     * The five fixed account types (used by the receipt quick-add modal).
     */
    public function types()
    {
        return AccountType::where('is_active', 1)->orderBy('id')->get(['id', 'name']);
    }

    /**
     * Sub type dropdown: only the children of the selected type.
     */
    public function subTypes($typeId)
    {
        return AccountSubType::where('account_type_id', $typeId)
            ->where('is_active', 1)
            ->orderBy('id')
            ->get(['id', 'name']);
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
        $error = $this->validateInput($request);
        if ($error) {
            return $error;
        }

        $credId = $request->client_credential_id ?: null;

        $head = AccountHead::create([
            'account_type_id'      => $request->account_type_id,
            'account_sub_type_id'  => $request->account_sub_type_id,
            'client_credential_id' => $credId,
            'code'                 => $request->code,
            'name'                 => $request->name,
            'normal_balance'       => $request->normal_balance,
            'is_active'            => 1,
        ]);

        return response()->json(['status' => 300, 'message' => 'Account head created successfully.', 'id' => $head->id]);
    }

    public function edit($id)
    {
        return response()->json(AccountHead::find($id));
    }

    public function update(Request $request)
    {
        $head = AccountHead::find($request->codeid);
        if (!$head) {
            return response()->json(['status' => 303, 'message' => "<div class='alert alert-warning'>Account head not found.</div>"]);
        }

        $error = $this->validateInput($request, $head);
        if ($error) {
            return $error;
        }

        $head->update([
            'account_type_id'      => $request->account_type_id,
            'account_sub_type_id'  => $request->account_sub_type_id,
            'client_credential_id' => $request->client_credential_id ?: null,
            'code'                 => $request->code,
            'name'                 => $request->name,
            'normal_balance'       => $request->normal_balance,
        ]);

        return response()->json(['status' => 300, 'message' => 'Account head updated successfully.']);
    }

    /**
     * Shared rules for store/update. Returns a JSON error response or null.
     */
    private function validateInput(Request $request, ?AccountHead $head = null)
    {
        if (empty($request->account_type_id)) {
            return response()->json(['status' => 303, 'message' => "<div class='alert alert-warning'>Please select an Account Type.</div>"]);
        }
        $type = AccountType::find($request->account_type_id);
        if (!$type || !$type->is_active) {
            return response()->json(['status' => 303, 'message' => "<div class='alert alert-warning'>Selected account type not found.</div>"]);
        }

        if (empty($request->account_sub_type_id)) {
            return response()->json(['status' => 303, 'message' => "<div class='alert alert-warning'>Please select an Account Sub Type.</div>"]);
        }
        $subType = AccountSubType::where('account_type_id', $type->id)->find($request->account_sub_type_id);
        if (!$subType || !$subType->is_active) {
            return response()->json(['status' => 303, 'message' => "<div class='alert alert-warning'>Selected sub type does not belong to that Account Type.</div>"]);
        }

        if (empty($request->code)) {
            return response()->json(['status' => 303, 'message' => "<div class='alert alert-warning'>Please fill Code field.</div>"]);
        }
        if (empty($request->name)) {
            return response()->json(['status' => 303, 'message' => "<div class='alert alert-warning'>Please fill Account Head field.</div>"]);
        }
        if (!in_array($request->normal_balance, ['debit', 'credit'], true)) {
            return response()->json(['status' => 303, 'message' => "<div class='alert alert-warning'>Please select the Normal Balance.</div>"]);
        }

        $credId = $request->client_credential_id ?: null;
        $ignore = $head?->id;

        if (AccountHead::where('code', $request->code)
            ->where('client_credential_id', $credId)
            ->when($ignore, fn($q) => $q->where('id', '!=', $ignore))
            ->exists()) {
            return response()->json(['status' => 303, 'message' => "<div class='alert alert-warning'>This code already exists for this client scope.</div>"]);
        }

        if (AccountHead::where('name', $request->name)
            ->where('account_sub_type_id', $subType->id)
            ->where('client_credential_id', $credId)
            ->when($ignore, fn($q) => $q->where('id', '!=', $ignore))
            ->exists()) {
            return response()->json(['status' => 303, 'message' => "<div class='alert alert-warning'>This account head already exists for this sub type and client scope.</div>"]);
        }

        return null;
    }

    public function delete($id)
    {
        AccountHead::destroy($id);
        return response()->json(['success' => true, 'message' => 'Deleted successfully.']);
    }

    public function toggleStatus($id)
    {
        $head = AccountHead::find($id);
        $head->is_active = !$head->is_active;
        $head->save();
        return response()->json(['success' => true]);
    }
}
