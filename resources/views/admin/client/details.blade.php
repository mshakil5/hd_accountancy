<form id="detailsForm">
    <div class="row my-4">
        <div class="col-lg-3 text-center d-none">
            <div class="img mb-2">
                <img src="{{ $client->photo ? asset('images/client/' . $client->photo) : asset('assets/img/human-placeholder.jpg') }}" id="imagePreview" width="150" class="border-theme border-2 rounded-3">
            </div>
            <label for="pic" class="mb-0" style="cursor: pointer;">
                <i class="bi bi-cloud-upload"></i>
                <small>Update Image</small>
            </label>
            <input type="file" id="pic" name="photo" class="invisible">
        </div>

        <div class="col-lg-4 field-group field-sole-trade field-self-assessment field-landlord field-limited-company field-partnership">
            <label for="" class="client-label">Client Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control my-2" name="name" value="{{ $client->name ?? '' }}" required>
        </div>

        <div class="col-lg-4 field-group field-limited-company">
            <label for="">Company Number</label>
            <input type="text" class="form-control my-2" id="company_number" name="company_number" value="{{ $client->company_number ?? '' }}">
        </div>

        <div class="col-lg-4 field-group field-sole-trade field-self-assessment field-landlord">
            <label for="">Date of Birth</label>
            <input type="date" class="form-control my-2" name="st_dob" value="{{ $client->dob ?? '' }}">
        </div>

        <div class="col-lg-4 field-group field-sole-trade field-self-assessment field-landlord field-limited-company field-partnership">
            <label for="">Primary Email <span class="text-danger">*</span></label>
            <input type="email" class="form-control my-2" id="email" name="email" required value="{{ $client->email ?? '' }}">
        </div>

         <div class="col-lg-4 field-group field-sole-trade field-self-assessment field-landlord field-limited-company field-partnership">
            <label for="">Secondary Email</label>
            <input type="email" class="form-control my-2" name="secondary_email" value="{{ $client->secondary_email ?? '' }}">
        </div>

        <div class="col-lg-4 field-group field-sole-trade field-self-assessment field-landlord field-limited-company field-partnership">
            <label for="">Primary Phone <span class="text-danger">*</span></label>
            <input type="number" class="form-control my-2" name="phone" value="{{ $client->phone }}" required>
        </div>

        <div class="col-lg-4 field-group field-sole-trade field-self-assessment field-landlord field-limited-company field-partnership">
            <label for="">Secondary Phone</label>
            <input type="number" class="form-control my-2" name="phone2" value="{{ $client->phone2 ?? '' }}">
        </div>

        <div class="col-lg-4 field-group field-sole-trade field-self-assessment field-landlord">
            <label for="">Address Line 1</label>
            <textarea class="form-control my-2" name="st_address_line1">{{ $client->address_line1 ?? '' }}</textarea>
        </div>

        <div class="col-lg-4 field-group field-sole-trade field-self-assessment field-landlord">
            <label for="">Address Line 2</label>
            <textarea class="form-control my-2" name="st_address_line2">{{ $client->address_line2 ?? '' }}</textarea>
        </div>

        <div class="col-lg-4 field-group field-limited-company">
            <label for="">Registered Address Line 1</label>
            <textarea class="form-control my-2" id="registered_address_line1" name="registered_address_line1">
                {{ $client->registered_address_line1 ?? '' }}
            </textarea>
        </div>

        <div class="col-lg-4 field-group field-limited-company">
            <label for="">Registered Address Line 2</label>
            <textarea class="form-control my-2" id="registered_address_line2" name="registered_address_line2">
                {{ $client->registered_address_line2 ?? '' }}
            </textarea>
        </div>

        <div class="col-lg-4 field-group field-sole-trade field-self-assessment field-landlord field-limited-company">
            <label for="">City</label>
            <input type="text" class="form-control my-2" id="city" name="city" value="{{ $client->city ?? '' }}">
        </div>

        <div class="col-lg-4 field-group field-sole-trade field-self-assessment field-landlord field-limited-company">
            <label for="">Country</label>
            <input type="text" class="form-control my-2" id="country" name="country" value="{{ $client->country ?? '' }}">
        </div>

        <div class="col-lg-4 field-group field-sole-trade field-self-assessment field-landlord field-limited-company">
            <label for="">Post Code</label>
            <input type="text" class="form-control my-2" id="postcode" name="postcode" value="{{ $client->postcode ?? '' }}">
        </div>

        <div class="col-lg-4 field-group field-limited-company field-partnership">
            <label for="">Trading Address Line 1</label>
            <textarea class="form-control my-2" id="trading_address_line1" name="trading_address_line1"> 
                {{ $client->trading_address_line1 ?? '' }}
            </textarea>
        </div>

        <div class="col-lg-4 field-group field-limited-company field-partnership">
            <label for="">Trading Address Line 2</label>
            <textarea class="form-control my-2" id="trading_address_line2" name="trading_address_line2">
                {{ $client->trading_address_line2 ?? '' }}
            </textarea>
        </div>

        <div class="col-lg-4 field-group field-limited-company field-partnership">
            <label for="">Trading City</label>
            <input type="text" class="form-control my-2" id="trading_city" name="trading_city" value="{{ $client->trading_city ?? '' }}">
        </div>

        <div class="col-lg-4 field-group field-limited-company field-partnership">
            <label for="">Trading Country</label>
            <input type="text" class="form-control my-2" id="trading_country" name="trading_country" value="{{ $client->trading_country ?? '' }}">
        </div>

        <div class="col-lg-4 field-group field-limited-company field-partnership">
            <label for="">Trading Post Code</label>
            <input type="text" class="form-control my-2" id="trading_postcode" name="trading_postcode" value="{{ $client->trading_postcode ?? '' }}">
        </div>

        <div class="col-lg-4 field-group field-sole-trade field-self-assessment field-landlord">
            <label for="">Photo ID Saved</label>
            <select class="form-control my-2" name="st_photo_id_saved">
                <option value="">Select</option>
                <option value="Y" {{ $client->photo_id_saved == 'Y' ? 'selected' : '' }}>Yes</option>
                <option value="N" {{ $client->photo_id_saved == 'N' ? 'selected' : '' }}>No</option>
            </select>
        </div>

        <div class="col-lg-4 field-group field-sole-trade field-self-assessment field-landlord">
            <label for="">HMRC Authorization</label>
            <select class="form-control my-2" name="st_hmrc_authorization">
                <option value="">Select</option>
                <option value="Y" {{ $client->hmrc_authorization == 'Y' ? 'selected' : '' }}>Yes</option>
                <option value="N" {{ $client->hmrc_authorization == 'N' ? 'selected' : '' }}>No</option>
            </select>
        </div>

        <div class="col-lg-4 field-group field-sole-trade field-self-assessment field-landlord">
            <label for="">UTR Number</label>
            <input type="text" class="form-control my-2" name="st_utr_number" value="{{ $client->utr_number ?? '' }}">
        </div>

       <div class="col-lg-4 field-group field-sole-trade field-self-assessment field-landlord">
            <label for="">NI Number</label>
            <input type="text" class="form-control my-2" name="st_ni_number" value="{{ $client->ni_number ?? '' }}">
        </div>

        <div class="col-lg-4 field-group field-sole-trade">
            <label for="">Business Name</label>
            <input type="text" class="form-control my-2" id="business_name" name="business_name" value="{{ $client->business_name ?? '' }}">
        </div>

        <div class="col-lg-4 field-group field-self-assessment">
            <label for="">Type of Business</label>
            <input type="text" class="form-control my-2" id="type_of_business" name="type_of_business" value="{{ $client->type_of_business ?? '' }}">
        </div>

        <div class="col-lg-4 field-group field-sole-trade field-self-assessment field-landlord field-limited-company field-partnership">
            <label for="">Agreement Date</label>
            <input type="date" class="form-control my-2" id="agreement_date" name="agreement_date" value="{{ $client->agreement_date ?? '' }}">
        </div>

        <div class="col-lg-4 field-group field-sole-trade field-self-assessment field-landlord field-limited-company field-partnership">
            <label for="">Cessation Date</label>
            <input type="date" class="form-control my-2" id="cessation_date" name="cessation_date" value="{{ $client->cessation_date ?? '' }}">
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
    </div>

    <div class="row">
        <div class="col-lg-4 mx-auto text-center">
            <button type="button" id="details-saveButton" class="btn btn-sm bg-theme text-light btn-outline-dark">Update</button>
        </div>
    </div>
</form>