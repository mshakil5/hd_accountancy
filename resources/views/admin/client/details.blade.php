<form id="detailsForm">
    <div class="row my-4">
        <div class="col-lg-3 text-center">
            <div class="img mb-2">
                <img src="{{ $client->photo ? asset('images/client/' . $client->photo) : asset('assets/img/human-placeholder.jpg') }}" id="imagePreview" width="150" class="border-theme border-2 rounded-3">
            </div>
            <label for="pic" class="mb-0" style="cursor: pointer;">
                <i class="bi bi-cloud-upload"></i>
                <small>Update Image</small>
            </label>
            <input type="file" id="pic" name="photo" class="invisible">
        </div>

        <!-- SOLE TRADER FIELDS -->
        <div class="col-lg-4 field-group field-sole-trader">
            <label for="">Client Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control my-2" name="st_name" value="{{ $client->name ?? '' }}" required>
        </div>

        <!-- SELF ASSESSMENT FIELDS -->
        <div class="col-lg-4 field-group field-self-assessment">
            <label for="">Client Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control my-2" name="sa_name" value="{{ $client->name ?? '' }}" required>
        </div>

        <!-- LANDLORD FIELDS -->
        <div class="col-lg-4 field-group field-landlord">
            <label for="">Client Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control my-2" name="ll_name" value="{{ $client->name ?? '' }}" required>
        </div>

        <!-- LIMITED COMPANY FIELDS -->
        <div class="col-lg-4 field-group field-limited-company">
            <label for="">Company Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control my-2" name="lc_company_name" value="{{ $client->company_name ?? '' }}" required>
        </div>

        <!-- PARTNERSHIP FIELDS -->
        <div class="col-lg-4 field-group field-partnership">
            <label for="">Business Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control my-2" name="p_business_name" value="{{ $client->partnership_business_name ?? '' }}" required>
        </div>

        <div class="col-lg-4 field-group field-limited-company">
            <label for="">Company Number</label>
            <input type="text" class="form-control my-2" name="lc_company_number" value="{{ $client->company_number ?? '' }}">
        </div>

        <div class="col-lg-4 field-group field-sole-trader">
            <label for="">Date of Birth</label>
            <input type="date" class="form-control my-2" name="st_dob" value="{{ $client->dob ?? '' }}">
        </div>

        <div class="col-lg-4 field-group field-self-assessment">
            <label for="">Date of Birth</label>
            <input type="date" class="form-control my-2" name="sa_dob" value="{{ $client->dob ?? '' }}">
        </div>

        <div class="col-lg-4 field-group field-landlord">
            <label for="">Date of Birth</label>
            <input type="date" class="form-control my-2" name="ll_dob" value="{{ $client->dob ?? '' }}">
        </div>

        <div class="col-lg-4 field-group">
            <label for="">Primary Email <span class="text-danger">*</span></label>
            <input type="email" class="form-control my-2" name="email" value="{{ $client->email }}" required>
        </div>

        <div class="col-lg-4 field-group">
            <label for="">Secondary Email</label>
            <input type="email" class="form-control my-2" name="secondary_email" value="{{ $client->secondary_email ?? '' }}">
        </div>

        <div class="col-lg-4 field-group">
            <label for="">Primary Phone <span class="text-danger">*</span></label>
            <input type="number" class="form-control my-2" name="phone" value="{{ $client->phone }}" required>
        </div>

        <div class="col-lg-4 field-group">
            <label for="">Secondary Phone</label>
            <input type="number" class="form-control my-2" name="phone2" value="{{ $client->phone2 ?? '' }}">
        </div>

        <div class="col-lg-4 field-group field-sole-trader">
            <label for="">Business Name</label>
            <input type="text" class="form-control my-2" name="st_business_name" value="{{ $client->business_name ?? '' }}">
        </div>

        <div class="col-lg-4 field-group field-self-assessment">
            <label for="">Type of Business</label>
            <input type="text" class="form-control my-2" name="sa_type_of_business" value="{{ $client->type_of_business ?? '' }}">
        </div>

        <div class="col-lg-4 field-group field-sole-trader">
            <label for="">Address Line 1</label>
            <textarea class="form-control my-2" name="st_address_line1">{{ $client->address_line1 ?? '' }}</textarea>
        </div>

        <div class="col-lg-4 field-group field-sole-trader">
            <label for="">Address Line 2</label>
            <textarea class="form-control my-2" name="st_address_line2">{{ $client->address_line2 ?? '' }}</textarea>
        </div>

        <div class="col-lg-4 field-group field-self-assessment">
            <label for="">Address Line 1</label>
            <textarea class="form-control my-2" name="sa_address_line1">{{ $client->address_line1 ?? '' }}</textarea>
        </div>

        <div class="col-lg-4 field-group field-self-assessment">
            <label for="">Address Line 2</label>
            <textarea class="form-control my-2" name="sa_address_line2">{{ $client->address_line2 ?? '' }}</textarea>
        </div>

        <div class="col-lg-4 field-group field-landlord">
            <label for="">Address Line 1</label>
            <textarea class="form-control my-2" name="ll_address_line1">{{ $client->address_line1 ?? '' }}</textarea>
        </div>

        <div class="col-lg-4 field-group field-landlord">
            <label for="">Address Line 2</label>
            <textarea class="form-control my-2" name="ll_address_line2">{{ $client->address_line2 ?? '' }}</textarea>
        </div>

        <div class="col-lg-4 field-group field-limited-company">
            <label for="">Registered Address Line 1</label>
            <textarea class="form-control my-2" name="lc_registered_address_line1">{{ $client->registered_address_line1 ?? '' }}</textarea>
        </div>

        <div class="col-lg-4 field-group field-limited-company">
            <label for="">Registered Address Line 2</label>
            <textarea class="form-control my-2" name="lc_registered_address_line2">{{ $client->registered_address_line2 ?? '' }}</textarea>
        </div>

        <div class="col-lg-4 field-group field-limited-company">
            <label for="">Trading Address Line 1</label>
            <textarea class="form-control my-2" name="lc_trading_address_line1">{{ $client->trading_address_line1 ?? '' }}</textarea>
        </div>

        <div class="col-lg-4 field-group field-limited-company">
            <label for="">Trading Address Line 2</label>
            <textarea class="form-control my-2" name="lc_trading_address_line2">{{ $client->trading_address_line2 ?? '' }}</textarea>
        </div>

        <div class="col-lg-4 field-group field-partnership">
            <label for="">Trading Address Line 1</label>
            <textarea class="form-control my-2" name="p_trading_address_line1">{{ $client->partnership_trading_address_line1 ?? '' }}</textarea>
        </div>

        <div class="col-lg-4 field-group field-partnership">
            <label for="">Trading Address Line 2</label>
            <textarea class="form-control my-2" name="p_trading_address_line2">{{ $client->partnership_trading_address_line2 ?? '' }}</textarea>
        </div>

        <div class="col-lg-4 field-group">
            <label for="">City</label>
            <input type="text" class="form-control my-2" name="city" value="{{ $client->city ?? '' }}">
        </div>

        <div class="col-lg-4 field-group">
            <label for="">Country</label>
            <input type="text" class="form-control my-2" name="country" value="{{ $client->country ?? '' }}">
        </div>

        <div class="col-lg-4 field-group">
            <label for="">Post Code</label>
            <input type="text" class="form-control my-2" name="postcode" value="{{ $client->postcode ?? '' }}">
        </div>

        <div class="col-lg-4 field-group field-sole-trader">
            <label for="">Photo ID Saved</label>
            <select class="form-control my-2" name="st_photo_id_saved">
                <option value="">Select</option>
                <option value="Y" {{ $client->photo_id_saved == 'Y' ? 'selected' : '' }}>Yes</option>
                <option value="N" {{ $client->photo_id_saved == 'N' ? 'selected' : '' }}>No</option>
            </select>
        </div>

        <div class="col-lg-4 field-group field-sole-trader">
            <label for="">HMRC Authorization</label>
            <select class="form-control my-2" name="st_hmrc_authorization">
                <option value="">Select</option>
                <option value="Y" {{ $client->hmrc_authorization == 'Y' ? 'selected' : '' }}>Yes</option>
                <option value="N" {{ $client->hmrc_authorization == 'N' ? 'selected' : '' }}>No</option>
            </select>
        </div>

        <div class="col-lg-4 field-group field-sole-trader">
            <label for="">UTR Number</label>
            <input type="text" class="form-control my-2" name="st_utr_number" value="{{ $client->utr_number ?? '' }}">
        </div>

        <div class="col-lg-4 field-group field-sole-trader">
            <label for="">NI Number</label>
            <input type="text" class="form-control my-2" name="st_ni_number" value="{{ $client->ni_number ?? '' }}">
        </div>

        <div class="col-lg-4 field-group field-self-assessment">
            <label for="">Photo ID Saved</label>
            <select class="form-control my-2" name="sa_photo_id_saved">
                <option value="">Select</option>
                <option value="Y" {{ $client->photo_id_saved == 'Y' ? 'selected' : '' }}>Yes</option>
                <option value="N" {{ $client->photo_id_saved == 'N' ? 'selected' : '' }}>No</option>
            </select>
        </div>

        <div class="col-lg-4 field-group field-self-assessment">
            <label for="">HMRC Authorization</label>
            <select class="form-control my-2" name="sa_hmrc_authorization">
                <option value="">Select</option>
                <option value="Y" {{ $client->hmrc_authorization == 'Y' ? 'selected' : '' }}>Yes</option>
                <option value="N" {{ $client->hmrc_authorization == 'N' ? 'selected' : '' }}>No</option>
            </select>
        </div>

        <div class="col-lg-4 field-group field-self-assessment">
            <label for="">UTR Number</label>
            <input type="text" class="form-control my-2" name="sa_utr_number" value="{{ $client->utr_number ?? '' }}">
        </div>

        <div class="col-lg-4 field-group field-self-assessment">
            <label for="">NI Number</label>
            <input type="text" class="form-control my-2" name="sa_ni_number" value="{{ $client->ni_number ?? '' }}">
        </div>

        <div class="col-lg-12 field-group field-landlord">
            <hr class="mt-4">
            <div class="row mb-2 align-items-center">
                <div class="col-md-6">
                    <button type="button" class="btn btn-sm btn-primary" id="add-property-btn">+ Add New Property</button>
                </div>
                <div class="col-md-6 text-end">
                    <label><strong>Number of Properties:</strong> <span id="property-count">{{ $client->properties->count() ?? 0 }}</span></label>
                </div>
            </div>
            <div class="row" id="property-address-wrapper">
                @if(isset($client) && $client->properties)
                    @foreach($client->properties as $index => $property)
                        <div class="col-md-4 property-group mb-3 position-relative">
                            <textarea name="properties[{{ $index }}][address]" class="form-control" rows="3" placeholder="Property Address">{{ $property->address }}</textarea>
                            <input type="hidden" name="properties[{{ $index }}][id]" value="{{ $property->id }}">
                            <button type="button" class="btn btn-sm btn-danger remove-property position-absolute top-0 end-0 translate-middle" style="width: 24px; height: 24px; padding: 0; border-radius: 50%;">×</button>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        <div class="col-lg-4 field-group field-landlord">
            <label for="">Photo ID Saved</label>
            <select class="form-control my-2" name="ll_photo_id_saved">
                <option value="">Select</option>
                <option value="Y" {{ $client->photo_id_saved == 'Y' ? 'selected' : '' }}>Yes</option>
                <option value="N" {{ $client->photo_id_saved == 'N' ? 'selected' : '' }}>No</option>
            </select>
        </div>

        <div class="col-lg-4 field-group field-landlord">
            <label for="">HMRC Authorization</label>
            <select class="form-control my-2" name="ll_hmrc_authorization">
                <option value="">Select</option>
                <option value="Y" {{ $client->hmrc_authorization == 'Y' ? 'selected' : '' }}>Yes</option>
                <option value="N" {{ $client->hmrc_authorization == 'N' ? 'selected' : '' }}>No</option>
            </select>
        </div>

        <div class="col-lg-4 field-group field-landlord">
            <label for="">UTR Number</label>
            <input type="text" class="form-control my-2" name="ll_utr_number" value="{{ $client->utr_number ?? '' }}">
        </div>

        <div class="col-lg-4 field-group field-landlord">
            <label for="">NI Number</label>
            <input type="text" class="form-control my-2" name="ll_ni_number" value="{{ $client->ni_number ?? '' }}">
        </div>

        <div class="col-lg-4 field-group">
            <label for="">Agreement Date</label>
            <input type="date" class="form-control my-2" name="agreement_date" value="{{ $client->agreement_date ?? '' }}">
        </div>

        <div class="col-lg-4 field-group">
            <label for="">Cessation Date</label>
            <input type="date" class="form-control my-2" name="cessation_date" value="{{ $client->cessation_date ?? '' }}">
        </div>

        <div class="col-lg-4 field-group field-sole-trader">
            <label for="">Agreement Date</label>
            <input type="date" class="form-control my-2" name="st_agreement_date" value="{{ $client->agreement_date ?? '' }}">
        </div>

        <div class="col-lg-4 field-group field-sole-trader">
            <label for="">Cessation Date</label>
            <input type="date" class="form-control my-2" name="st_cessation_date" value="{{ $client->cessation_date ?? '' }}">
        </div>

        <div class="col-lg-4 field-group field-self-assessment">
            <label for="">Agreement Date</label>
            <input type="date" class="form-control my-2" name="sa_agreement_date" value="{{ $client->agreement_date ?? '' }}">
        </div>

        <div class="col-lg-4 field-group field-self-assessment">
            <label for="">Cessation Date</label>
            <input type="date" class="form-control my-2" name="sa_cessation_date" value="{{ $client->cessation_date ?? '' }}">
        </div>

        <div class="col-lg-4 field-group field-landlord">
            <label for="">Agreement Date</label>
            <input type="date" class="form-control my-2" name="ll_agreement_date" value="{{ $client->agreement_date ?? '' }}">
        </div>

        <div class="col-lg-4 field-group field-landlord">
            <label for="">Cessation Date</label>
            <input type="date" class="form-control my-2" name="ll_cessation_date" value="{{ $client->cessation_date ?? '' }}">
        </div>

        <div class="col-lg-4 field-group field-limited-company">
            <label for="">Agreement Date</label>
            <input type="date" class="form-control my-2" name="lc_agreement_date" value="{{ $client->agreement_date ?? '' }}">
        </div>

        <div class="col-lg-4 field-group field-limited-company">
            <label for="">Cessation Date</label>
            <input type="date" class="form-control my-2" name="lc_cessation_date" value="{{ $client->cessation_date ?? '' }}">
        </div>

        <div class="col-lg-4 field-group field-partnership">
            <label for="">Agreement Date</label>
            <input type="date" class="form-control my-2" name="p_agreement_date" value="{{ $client->agreement_date ?? '' }}">
        </div>

        <div class="col-lg-4 field-group field-partnership">
            <label for="">Cessation Date</label>
            <input type="date" class="form-control my-2" name="p_cessation_date" value="{{ $client->cessation_date ?? '' }}">
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4 mx-auto text-center">
            <button type="button" id="details-saveButton" class="btn btn-sm bg-theme text-light btn-outline-dark">Update</button>
        </div>
    </div>
</form>