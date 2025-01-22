<form id="businessForm">
    <div class="row my-4">
        <div class="col-lg-4">
            <label for="status">Company status <span class="text-danger">*</span></label>
            <div class="mt-2">
                <select class="form-control my-2" name="status" id="status">
                    <option value="0" {{ isset($businessInfo->status) && $businessInfo->status == 0 ? 'selected' : '' }}>Inactive</option>
                    <option value="1" {{ isset($businessInfo->status) && $businessInfo->status == 1 ? 'selected' : '' }}>Active</option>
                </select>
            </div>
        </div>
        <div class="col-lg-4">
            <label for="nature_of_business">Nature Of Business</label>
            <input type="text" class="form-control my-2" id="nature_of_business" name="nature_of_business" placeholder="Enter nature of business" value="{{ $businessInfo->nature_of_business ?? '' }}">
        </div>
        <div class="col-lg-4">
            <label for="company_number">Company Number <span class="text-danger">*</span></label>
            <input type="number" class="form-control my-2" id="company_number" name="company_number" placeholder="Enter company number" value="{{ $businessInfo->company_number ?? '' }}">
        </div>
        <div class="col-lg-4">
            <label for="authorization_code">Company Auth Code <span class="text-danger">*</span></label>
            <input type="text" class="form-control my-2" id="authorization_code" name="authorization_code" placeholder="Enter company auth code" value="{{ $businessInfo->authorization_code ?? '' }}">
        </div>
        <div class="col-lg-4">
            <label for="company_utr">Company UTR <span class="text-danger">*</span></label>
            <input type="number" class="form-control my-2" id="company_utr" name="company_utr" placeholder="Enter UTR number" value="{{ $businessInfo->company_utr ?? '' }}">
        </div>
        <div class="col-lg-4">
            <label for="ct_authorization">CT Authorization</label>
            <div class="mt-2">
                <select class="form-control my-2" name="ct_authorization" id="ct_authorization">
                    <option value="0" {{ isset($businessInfo->ct_authorization) && $businessInfo->ct_authorization == 0 ? 'selected' : '' }}>No</option>
                    <option value="1" {{ isset($businessInfo->ct_authorization) && $businessInfo->ct_authorization == 1 ? 'selected' : '' }}>Yes</option>
                </select>
            </div>
        </div>
        <div class="col-lg-4">
            <label for="paye_ref_number">PAYE Ref Number</label>
            <input type="number" class="form-control my-2" id="paye_ref_number" name="paye_ref_number" placeholder="Enter UTR number" value="{{ $businessInfo->paye_ref_number ?? '' }}">
        </div>
        <div class="col-lg-4">
            <label for="paye_authorization">PAYE Authorization</label>
            <div class="mt-2">
                <select class="form-control my-2" name="paye_authorization" id="paye_authorization">
                    <option value="0" {{ isset($businessInfo->paye_authorization) && $businessInfo->paye_authorization == 0 ? 'selected' : '' }}>No</option>
                    <option value="1" {{ isset($businessInfo->paye_authorization) && $businessInfo->paye_authorization == 1 ? 'selected' : '' }}>Yes</option>
                </select>
            </div>
        </div>
        <div class="col-lg-4">
            <label for="account_office_ref_number">Account Office Ref Number</label>
            <input type="text" class="form-control my-2" id="account_office_ref_number" name="account_office_ref_number" placeholder="Enter UTR number" value="{{ $businessInfo->account_office_ref_number ?? '' }}">
        </div>
        <div class="col-lg-4">
            <label for="vat_number">VAT Number</label>
            <input type="number" class="form-control my-2" id="vat_number" name="vat_number" placeholder="Enter VAT number" value="{{ $businessInfo->vat_number ?? '' }}">
        </div>
        <div class="col-lg-4">
            <label for="vat_authorization">Vat Authorization</label>
            <div class="mt-2">
                <select class="form-control my-2" name="vat_authorization" id="vat_authorization">
                    <option value="0" {{ isset($businessInfo->vat_authorization) && $businessInfo->vat_authorization == 0 ? 'selected' : '' }}>No</option>
                    <option value="1" {{ isset($businessInfo->vat_authorization) && $businessInfo->vat_authorization == 1 ? 'selected' : '' }}>Yes</option>
                </select>
            </div>
        </div>
        <div class="col-lg-4">
            <label for="year_end_date">Year End Date</label>
            <input type="date" class="form-control my-2" id="year_end_date" name="year_end_date" placeholder="Enter year end date" value="{{ $businessInfo->year_end_date ?? '' }}">
        </div>
        <div class="col-lg-4 d-none">
            <label for="due_date">Due Date</label>
            <input type="date" class="form-control my-2" id="due_date" name="due_date" placeholder="Enter due date" value="{{ $businessInfo->due_date ?? '' }}">
        </div>
        <div class="col-lg-4">
            <label for="confirmation_due_date">Confirmation Date</label>
            <input type="date" class="form-control my-2" id="confirmation_due_date" name="confirmation_due_date" placeholder="Enter confirmation date" value="{{ $businessInfo->confirmation_due_date ?? '' }}">
        </div>
    </div>
    <div class="row">
        <div class="col-lg-4 mx-auto text-center">
            <button id="business-saveButton" class="btn btn-sm bg-theme text-light btn-outline-dark">Update</button>
        </div>
    </div>
</form>