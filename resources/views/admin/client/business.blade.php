<form id="businessForm">
    <div class="row my-4">
        <div class="col-lg-4">
            <label for="">Nature Of Business</label>
            <input type="text" class="form-control my-2" id="nature_of_business" name="nature_of_business" placeholder="Enter nature of business" value="@isset($client->businessInfo){{ $client->businessInfo->nature_of_business }}@endisset">
        </div>
        <div class="col-lg-4">
            <label for="">Company Number</label>
            <input type="number" class="form-control my-2" id="company_number" name="company_number" placeholder="Enter company number" value="@isset($client->businessInfo){{ $client->businessInfo->company_number }}@endisset">
        </div>
        <div class="col-lg-4">
            <label for="">Year End Date</label>
            <input type="date" class="form-control my-2" id="year_end_date" name="year_end_date" placeholder="Enter year end date" value="@isset($client->businessInfo){{ $client->businessInfo->year_end_date }}@endisset">
        </div>
        <div class="col-lg-4">
            <label for="">Due Date</label>
            <input type="date" class="form-control my-2" id="due_date" name="due_date" placeholder="Enter due date" value="@isset($client->businessInfo){{ $client->businessInfo->due_date }}@endisset">
        </div>
        <div class="col-lg-4">
            <label for="">Confirmation Due Date</label>
            <input type="date" class="form-control my-2" id="confirmation_due_date" name="confirmation_due_date" placeholder="Enter due date again" value="@isset($client->businessInfo){{ $client->businessInfo->confirmation_due_date }}@endisset">
        </div>
        <div class="col-lg-4">
            <label for="">Authorization Code</label>
            <input type="text" class="form-control my-2" id="authorization_code" name="authorization_code" placeholder="Enter authorization code" value="@isset($client->businessInfo){{ $client->businessInfo->authorization_code }}@endisset">
        </div>
        <div class="col-lg-4">
            <label for="">Company UTR</label>
            <input type="number" class="form-control my-2" id="company_utr" name="company_utr" placeholder="Enter UTR number" value="@isset($client->businessInfo){{ $client->businessInfo->company_utr }}@endisset">
        </div>
        <div class="col-lg-4">
            <label for="">Company status</label>
            <div class="mt-2">
                <select class="form-control my-2" name="status" id="status">
                    <!-- <option value="" selected>Select status</option> -->
                    <option value="0" {{ isset($client->businessInfo) && $client->businessInfo->status == '0' ? 'selected' : '' }}>Inactive</option>
                    <option value="1" {{ isset($client->businessInfo) && $client->businessInfo->status == '1' ? 'selected' : '' }}>Active</option>
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
    </div>
    <div class="row">
        <div class="col-lg-4 mx-auto text-center">
            @if(isset($client->businessInfo))
            <button id="business-updateButton" class="btn btn-sm bg-theme text-light btn-outline-dark">Update</button>
            @else
            <button id="business-clearButton" class="btn btn-sm btn-outline-dark">Clear</button>
            <button id="business-saveButton" class="btn btn-sm bg-theme text-light btn-outline-dark">Save</button>
            @endif
        </div>
    </div>
</form>
