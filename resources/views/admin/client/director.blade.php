<form id="directorForm">
    <div class="row my-4">
        <div class="col-lg-4">
            <label for="">Client reference</label>
                <div class="mt-2">
                <select class="form-control my-2 select2" id="client_id" name="client_id">
                    <option value="" selected disabled>Choose client reference</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->refid }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-lg-4">
            <label for="">Director Name</label>
            <input type="text" class="form-control my-2" id="name" name="name" placeholder="Enter name">
        </div>
        <div class="col-lg-4">
            <label for="">Director Phone</label>
            <input type="number" class="form-control my-2" id="phone" name="phone" placeholder="Enter phone number">
        </div>
        <div class="col-lg-4">
            <label for="">Director Email</label>
            <input type="email" class="form-control my-2" id="email" name="email" placeholder="Enter email">
        </div>
        <div class="col-lg-4">
            <label for=""> Director Address</label>
            <input type="text" class="form-control my-2" id="address" name="address" placeholder="Enter address">
        </div>
        <div class="col-lg-4">
            <label for="">Date Of Birth</label>
            <input type="date" class="form-control my-2" id="confirmation_due_date" name="dob" placeholder="Enter date of birth">
        </div>
        <div class="col-lg-4">
            <label for="">NI Number</label>
            <input type="number" class="form-control my-2" id="ni_number" name="ni_number" placeholder="Enter NI number">
        </div>
        <div class="col-lg-4">
            <label for="">UTR Number</label>
            <input type="number" class="form-control my-2" id="utr_number" name="utr_number" placeholder="Enter UTR number">
        </div>
        <div class="col-lg-4">
            <label for="">UTR Authorization</label>
            <input type="number" class="form-control my-2" id="utr_authorization" name="utr_authorization" placeholder="Enter UTR authorization">
        </div>
        <div class="col-lg-4">
            <label for="">NINO</label>
            <input type="number" class="form-control my-2" id="nino" name="nino" placeholder="Enter NINO number">
        </div>
        <div class="col-lg-4">
            <label for="">Company status</label>
            <div class="mt-2">
                <select class="form-control my-2" name="status" id="status">
                    <option value="" selected disabled>Select status</option>
                    <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Inactive</option>
                    <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Active</option>
                </select>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-4 mx-auto text-center">
            <button id="director-clearButton" class="btn btn-sm btn-outline-dark">Clear</button>
            <button id="director-saveButton"  class="btn btn-sm bg-theme text-light btn-outline-dark">Save</button>
        </div>
    </div>
</form>