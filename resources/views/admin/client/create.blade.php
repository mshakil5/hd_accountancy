@extends('admin.layouts.admin')

@section('content')

<section class="section">
    <div class="row">
        <div class="col-lg-12 px-0 shadow-sm border-theme border-2">
            <p class="p-2 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center">
                <i class='bx bxs-user-plus fs-4 me-2'></i> New Client Entry
            </p>

            <div class="row my-4 px-3">
                <div class="col-lg-3">
                    <label for="">Select Client <span class="text-danger">*</span></label>
                    <select name="client_credential_id" id="client_credential_id" class="form-control mt-2 select2">
                        <option value="">Please Select</option>
                        @foreach ($clientCridentials as $clientCridential)
                            <option value="{{ $clientCridential->id }}">{{$clientCridential->first_name}} {{$clientCridential->last_name}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3">
                    <label for="">Client Type <span class="text-danger">*</span></label>
                    <select name="client_type_id" class="form-control mt-2" id="client_type_id">
                        <option value="">Select client</option>
                        @foreach($clientTypes as $clientType)
                            <option value="{{ $clientType->id }}" data-type="{{ strtolower($clientType->name) }}">{{ $clientType->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3">
                    <label for="">Client Reference <span class="text-danger">*</span></label>
                    <input type="text" class="form-control my-2" id="reference_id" name="reference_id" placeholder="Ex: LT-001">
                </div>
                <div class="col-lg-3">
                    <label for="">Client Manager</label>
                    <select class="form-control mt-2 select2" name="manager_id" id="manager_id">
                        <option value="">Select manager</option>
                        @foreach($managers as $manager)
                            <option value="{{ $manager->id }}">{{ $manager->first_name }} {{ $manager->last_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row px-3">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body border-theme border-2">
                            <div class="tab-content pt-2">
                                <form id="detailsForm">
                                    <div class="row my-4">
                                        <div class="col-lg-4 d-none">
                                            <label for="">Client Reference <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control my-2" id="client_reference" name="client_reference" required>
                                        </div>

                                        <div class="col-lg-4 field-group field-sole-trade field-self-assessment field-landlord field-limited-company field-partnership">
                                            <label for="" class="client-label">Client Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control my-2" id="name" name="name">
                                        </div>

                                        <div class="col-lg-4 field-group field-limited-company">
                                            <label for="">Company Number</label>
                                            <input type="text" class="form-control my-2" id="company_number" name="company_number">
                                        </div>

                                        <div class="col-lg-4 field-group field-sole-trade field-self-assessment field-landlord">
                                            <label for="">Date of Birth</label>
                                            <input type="date" class="form-control my-2" id="dob" name="dob">
                                        </div>

                                        <div class="col-lg-4 field-group field-sole-trade field-self-assessment field-landlord field-limited-company field-partnership">
                                            <label for="">Primary Email <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control my-2" id="email" name="email" required>
                                        </div>

                                        <div class="col-lg-4 field-group field-sole-trade field-self-assessment field-landlord field-limited-company field-partnership">
                                            <label for="">Secondary Email</label>
                                            <input type="email" class="form-control my-2" id="secondary_email" name="secondary_email">
                                        </div>

                                        <div class="col-lg-4 field-group field-sole-trade field-self-assessment field-landlord field-limited-company field-partnership">
                                            <label for="">Primary Phone <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control my-2" id="phone" name="phone" required>
                                        </div>

                                        <div class="col-lg-4 field-group field-sole-trade field-self-assessment field-landlord field-limited-company field-partnership">
                                            <label for="">Secondary Phone</label>
                                            <input type="number" class="form-control my-2" id="phone2" name="phone2">
                                        </div>

                                        <div class="col-lg-4 field-group field-sole-trade field-self-assessment field-landlord">
                                            <label for="">Address Line 1</label>
                                            <textarea class="form-control my-2" id="address_line1" name="address_line1"></textarea>
                                        </div>

                                        <div class="col-lg-4 field-group field-sole-trade field-self-assessment field-landlord">
                                            <label for="">Address Line 2</label>
                                            <textarea class="form-control my-2" id="address_line2" name="address_line2"></textarea>
                                        </div>

                                        <div class="col-lg-4 field-group field-limited-company">
                                            <label for="">Registered Address Line 1</label>
                                            <textarea class="form-control my-2" id="registered_address_line1" name="registered_address_line1"></textarea>
                                        </div>

                                        <div class="col-lg-4 field-group field-limited-company">
                                            <label for="">Registered Address Line 2</label>
                                            <textarea class="form-control my-2" id="registered_address_line2" name="registered_address_line2"></textarea>
                                        </div>

                                         <div class="col-lg-4 field-group field-sole-trade field-self-assessment field-landlord field-limited-company">
                                            <label for="">City</label>
                                            <input type="text" class="form-control my-2" id="city" name="city">
                                        </div>

                                        <div class="col-lg-4 field-group field-sole-trade field-self-assessment field-landlord field-limited-company">
                                            <label for="">Country</label>
                                            <input type="text" class="form-control my-2" id="country" name="country">
                                        </div>

                                        <div class="col-lg-4 field-group field-sole-trade field-self-assessment field-landlord field-limited-company">
                                            <label for="">Post Code</label>
                                            <input type="text" class="form-control my-2" id="postcode" name="postcode">
                                        </div>

                                        <div class="col-lg-4 field-group field-limited-company field-partnership">
                                            <label for="">Trading Address Line 1</label>
                                            <textarea class="form-control my-2" id="trading_address_line1" name="trading_address_line1"></textarea>
                                        </div>

                                        <div class="col-lg-4 field-group field-limited-company field-partnership">
                                            <label for="">Trading Address Line 2</label>
                                            <textarea class="form-control my-2" id="trading_address_line2" name="trading_address_line2"></textarea>
                                        </div>

                                        <div class="col-lg-4 field-group field-limited-company field-partnership">
                                            <label for="">Trading City</label>
                                            <input type="text" class="form-control my-2" id="trading_city" name="trading_city">
                                        </div>

                                        <div class="col-lg-4 field-group field-limited-company field-partnership">
                                            <label for="">Trading Country</label>
                                            <input type="text" class="form-control my-2" id="trading_country" name="trading_country">
                                        </div>

                                        <div class="col-lg-4 field-group field-limited-company field-partnership">
                                            <label for="">Trading Post Code</label>
                                            <input type="text" class="form-control my-2" id="trading_postcode" name="trading_postcode">
                                        </div>

                                        <div class="col-lg-4 field-group field-sole-trade field-self-assessment field-landlord">
                                            <label for="">Photo ID Saved</label>
                                            <select class="form-control my-2" id="photo_id_saved" name="photo_id_saved">
                                                <option value="">Select</option>
                                                <option value="Y">Yes</option>
                                                <option value="N">No</option>
                                            </select>
                                        </div>

                                        <div class="col-lg-4 field-group field-sole-trade field-self-assessment field-landlord">
                                            <label for="">HMRC Authorization</label>
                                            <select class="form-control my-2" id="hmrc_authorization" name="hmrc_authorization">
                                                <option value="">Select</option>
                                                <option value="Y">Yes</option>
                                                <option value="N">No</option>
                                            </select>
                                        </div>

                                        <div class="col-lg-4 field-group field-sole-trade field-self-assessment field-landlord">
                                            <label for="">UTR Number</label>
                                            <input type="text" class="form-control my-2" id="utr_number" name="utr_number">
                                        </div>

                                        <div class="col-lg-4 field-group field-sole-trade field-self-assessment field-landlord">
                                            <label for="">NI Number</label>
                                            <input type="text" class="form-control my-2" id="ni_number" name="ni_number">
                                        </div>

                                        <div class="col-lg-4 field-group field-sole-trade">
                                            <label for="">Business Name</label>
                                            <input type="text" class="form-control my-2" id="business_name" name="business_name">
                                        </div>

                                        <div class="col-lg-4 field-group field-self-assessment">
                                            <label for="">Type of Business</label>
                                            <input type="text" class="form-control my-2" id="type_of_business" name="type_of_business">
                                        </div>

                                        <div class="col-lg-4 field-group field-sole-trade field-self-assessment field-landlord field-limited-company field-partnership">
                                            <label for="">Agreement Date</label>
                                            <input type="date" class="form-control my-2" id="agreement_date" name="agreement_date">
                                        </div>

                                        <div class="col-lg-4 field-group field-sole-trade field-self-assessment field-landlord field-limited-company field-partnership">
                                            <label for="">Cessation Date</label>
                                            <input type="date" class="form-control my-2" id="cessation_date" name="cessation_date">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-lg-4 mx-auto text-center">
                                            <button id="details-clearButton" class="btn btn-sm btn-outline-dark">Clear</button>
                                            <button id="details-saveButton" class="btn btn-sm bg-theme text-light btn-outline-dark">Save</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@section('script')

<script>
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    function hideAllFields() {
        $('.field-group').hide();
    }

    function showFieldsByType(type) {
        hideAllFields();
        const typeLower = type.toLowerCase().trim();

        if (typeLower === 'sole trade') {
            $('.field-sole-trade').show();
        } else if (typeLower === 'self assessment' || typeLower === 'self assesment') {
            $('.field-self-assessment').show();
        } else if (typeLower === 'landlord') {
            $('.field-landlord').show();
        } else if (typeLower === 'limited company') {
            $('.field-limited-company').show();
        } else if (typeLower === 'partnership') {
            $('.field-partnership').show();
        }
    }

    function updateClientLabel(type) {
        const typeLower = type.toLowerCase();
        const label = $('.client-label');
        
        if (typeLower === 'limited company') {
            label.html('Company Name <span class="text-danger">*</span>');
        } else if (typeLower === 'partnership') {
            label.html('Business Name <span class="text-danger">*</span>');
        } else {
            label.html('Client Name <span class="text-danger">*</span>');
        }
    }

    $('#client_type_id').on('change', function() {
        const type = $(this).find('option:selected').data('type');
        showFieldsByType(type);
        updateClientLabel(type);
    });

    $(document).ready(function() {
        hideAllFields();
        const initialType = $('#client_type_id').find('option:selected').data('type');
        if (initialType) {
            showFieldsByType(initialType);
            updateClientLabel(initialType);
        }
    });

    $('#details-saveButton').click(function(event) {
        event.preventDefault();
        var formData = new FormData($('#detailsForm')[0]);
        formData.append('client_credential_id', $('#client_credential_id').val());
        formData.append('client_type_id', $('#client_type_id').val());
        formData.append('manager_id', $('#manager_id').val());
        formData.append('reference_id', $('#reference_id').val());

        $.ajax({
            url: "{{URL::to('/admin/client')}}",
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.status === 200) {
                    toastr.success("Client created successfully", "Success!");
                    setTimeout(() => {
                        window.location.href = "{{ route('client.update.form', ':id') }}".replace(':id', response.client_id);
                    }, 500);
                } else {
                    toastr.error(response.message, "Error");
                }
            },
            error: function(xhr) {
                var errorMessage = "An error occurred.";
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    errorMessage = Object.values(xhr.responseJSON.errors)[0][0];
                }
                toastr.error(errorMessage, "Error");
            }
        });
    });

    $('#details-clearButton').click(function(e) {
        e.preventDefault();
        $('#detailsForm')[0].reset();
    });
</script>

@endsection