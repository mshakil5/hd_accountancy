<form id="businessForm">
    <div class="row my-4">
        <div class="col-lg-4">
            <label for="nature_of_business">Nature Of Business</label>
            <input type="text" class="form-control my-2" id="nature_of_business" name="nature_of_business" placeholder="Enter nature of business" value="{{ $businessInfo->nature_of_business ?? '' }}">
        </div>
        <div class="col-lg-4">
            <label for="company_number">Company Number</label>
            <input type="number" class="form-control my-2" id="company_number" name="company_number" placeholder="Enter company number" value="{{ $businessInfo->company_number ?? '' }}">
        </div>
        <div class="col-lg-4">
            <label for="year_end_date">Year End Date</label>
            <input type="date" class="form-control my-2" id="year_end_date" name="year_end_date" placeholder="Enter year end date" value="{{ $businessInfo->year_end_date ?? '' }}">
        </div>
        <div class="col-lg-4">
            <label for="due_date">Due Date</label>
            <input type="date" class="form-control my-2" id="due_date" name="due_date" placeholder="Enter due date" value="{{ $businessInfo->due_date ?? '' }}">
        </div>
        <div class="col-lg-4">
            <label for="confirmation_due_date">Confirmation Due Date</label>
            <input type="date" class="form-control my-2" id="confirmation_due_date" name="confirmation_due_date" placeholder="Enter due date again" value="{{ $businessInfo->confirmation_due_date ?? '' }}">
        </div>
        <div class="col-lg-4">
            <label for="authorization_code">Authorization Code</label>
            <input type="number" class="form-control my-2" id="authorization_code" name="authorization_code" placeholder="Enter authorization code" value="{{ $businessInfo->authorization_code ?? '' }}">
        </div>
        <div class="col-lg-4">
            <label for="company_utr">Company UTR</label>
            <input type="number" class="form-control my-2" id="company_utr" name="company_utr" placeholder="Enter UTR number" value="{{ $businessInfo->company_utr ?? '' }}">
        </div>
        <div class="col-lg-4">
            <label for="status">Company status</label>
            <div class="mt-2">
                <select class="form-control my-2" name="status" id="status">
                    <!-- <option value="" selected disabled>Select status</option> -->
                    <option value="0" {{ isset($businessInfo->status) && $businessInfo->status == 0 ? 'selected' : '' }}>Inactive</option>
                    <option value="1" {{ isset($businessInfo->status) && $businessInfo->status == 1 ? 'selected' : '' }}>Active</option>
                </select>
            </div>
        </div>
        <div class="col-lg-4">
            <label for="">HMRC Authorisation</label>
            <div class="mt-2">
                <select class="form-control my-2" name="hmrc_authorisation" id="hmrc_authorisation">
                    <!-- <option value="" selected>Select status</option> -->
                    <option value="0" {{ isset($client->businessInfo) && $client->businessInfo->status == '0' ? 'selected' : '' }}>No</option>
                    <option value="1" {{ isset($client->businessInfo) && $client->businessInfo->status == '1' ? 'selected' : '' }}>Yes</option>
                </select>
            </div>
        </div>
        <div class="col-lg-4">
            <label for="">VAT</label>
            <input type="number" class="form-control my-2" id="vat_number" name="vat_number" placeholder="Enter vat number" value="@isset($client->businessInfo){{ $client->businessInfo->vat_number }}@endisset">
        </div>
    </div>
    <div class="row">
        <div class="col-lg-4 mx-auto text-center">
            <button  id="business-saveButton" class="btn btn-sm bg-theme text-light btn-outline-dark">Update</button>
        </div>
    </div>
</form>
