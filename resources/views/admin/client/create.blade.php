@extends('admin.layouts.admin')

@section('content')

<section class="section">
    <div class="row">
        <div class="col-lg-12 px-0   shadow-sm border-theme border-2">
            <p class="p-2 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center">
                <i class='bx bxs-user-plus fs-4 me-2'></i> New Client Entry
            </p>

            <!-- Top 4 -->
            <div class="row my-4 px-3">
                <div class="col-lg-3">
                    <label for="">Client Name <span class="text-danger">*</span></label>
                    <input for="name" type="text" value="{{ isset($client->name) ? $client->name : '' }}" class="form-control mt-2" name="name" id="name" required placeholder="Ex: John">
                </div>
                <div class="col-lg-3">
                    <label for="">Reference ID <span class="text-danger">*</span></label>
                    <input type="text" class="form-control my-2" id="reference_id" name="reference_id" placeholder="Ex: LT-001" value="{{ isset($client) && isset($client->refid) ? $client->refid : '' }}">
                </div>
                <div class="col-lg-3">
                    <label for="">Client Type <span class="text-danger">*</span></label>
                    <div class="mt-2">
                        <select name="client_type_id" class="form-control mt-2" id="client_type_id">
                            <option value="" selected>Select client</option>
                            @foreach($clientTypes as $clientType)
                            <option value="{{ $clientType->id }}" {{ isset($client->clientType) && $client->clientType->id == $clientType->id ? 'selected' : '' }}>{{ $clientType->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-lg-3">
                    <label for="">Client Manager</label>
                    <div class="mt-2">
                        <select class="form-control mt-2" name="manager_id" id="manager_id">
                            <option value="" selected>Select manager</option>
                            @foreach($managers as $manager)
                            <option value="{{ $manager->id }}" {{ isset($client->manager) && $client->manager->id == $manager->id ? 'selected' : '' }}>{{ $manager->first_name }} {{ $manager->first_name }} ({{ $manager->type }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <!-- Top 4 -->
            <div class="row px-3">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body border-theme border-2">
                            <!-- Default Tabs -->
                            <ul class="nav nav-tabs mt-4 d-flex border-theme" id="myTabjustified" role="tablist">
                                <li class="nav-item flex-fill" role="presentation">
                                    <button class="nav-link w-100 active" id="home-tab" data-bs-toggle="tab" data-bs-target="#home" type="button" role="tab" aria-controls="home" aria-selected="true">Client Details</button>
                                </li>
                                <li class="nav-item flex-fill" role="presentation">
                                    <button class="nav-link w-100 @if(!isset($client->id)) disabled @endif" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="false">Business Info</button>
                                </li>
                                <li class="nav-item flex-fill" role="presentation" id="directorLI">
                                    <button class="nav-link w-100 @if(!isset($client->id)) disabled @endif" id="contact-tab" data-bs-toggle="tab" data-bs-target="#contact" type="button" role="tab" aria-controls="contact" aria-selected="false">Director Info</button>
                                </li>
                                <li class="nav-item flex-fill" role="presentation">
                                    <button class="nav-link w-100 @if(!isset($client->id)) disabled @endif" id="service-tab" data-bs-toggle="tab" data-bs-target="#service" type="button" role="tab" aria-controls="service" aria-selected="false">Service list</button>
                                </li>
                                <li class="nav-item flex-fill" role="presentation">
                                    <button class="nav-link w-100 @if(!isset($client->id)) disabled @endif" id="contact-info-tab" data-bs-toggle="tab" data-bs-target="#contact-info" type="button" role="tab" aria-controls="contact-info" aria-selected="false">Contact-info</button>
                                </li>
                                <li class="nav-item flex-fill d-none" role="presentation">
                                    <button class="nav-link w-100 @if(!isset($client->id)) disabled @endif" id="custom-field-tab" data-bs-toggle="tab" data-bs-target="#custom-field" type="button" role="tab" aria-controls="custom-field" aria-selected="false">Custom-field</button>
                                </li>
                                <li class="nav-item flex-fill" role="presentation">
                                    <button class="nav-link w-100 @if(!isset($client->id)) disabled @endif" id="recent-update-tab" data-bs-toggle="tab" data-bs-target="#recent-update" type="button" role="tab" aria-controls="recent-update" aria-selected="false">Recent-update</button>
                                </li>
                            </ul>
                            <!-- Tabs end -->

                            <div class="tab-content pt-2" id="myTabjustifiedContent">
                                <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
                                    <form id="detailsForm">
                                        <div class="row my-4">
                                            <div class="col-lg-9">
                                            </div>
                                            <div class="col-lg-3 text-center">
                                                <div class="img mb-2">
                                                    @if(isset($client->photo) && $client->photo)
                                                    <img src="{{ asset('images/client/' . $client->photo) }}" id="imagePreview" width="150" class="border-theme border-2 rounded-3">
                                                    @else
                                                    <img src="{{ asset('assets/img/human-placeholder.jpg') }}" id="imagePreview" width="150" class="border-theme border-2 rounded-3">
                                                    @endif
                                                </div>
                                                <label for="pic" class="mb-0" style="cursor: pointer;">
                                                    <i class="bi bi-cloud-upload"></i>
                                                    <small>Upload Image</small>
                                                </label>
                                                <input type="file" id="pic" name="photo" class="invisible">
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">Email <span class="text-danger">*</span></label>
                                                <input type="email" class="form-control my-2" id="email" name="email" value="{{ isset($client) && isset($client->email) ? $client->email : '' }}" placeholder="Ex: john@example.com" required>
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">Primary Phone <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control my-2" id="phone" name="phone" value="{{ isset($client) && isset($client->phone) ? $client->phone : '' }}" placeholder="Ex: 1234567890" required>
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">Secondary Phone </label>
                                                <input type="number" class="form-control my-2" id="phone2" name="phone2" value="{{ isset($client) && isset($client->phone2) ? $client->phone2 : '' }}" placeholder="Ex: 1234567890">
                                            </div>
                                            
                                            <div class="col-lg-4">
                                                <label for="">Trading Address</label>
                                                <textarea class="form-control my-2" id="trading_address" name="trading_address" placeholder="Ex: 34 WHITE ROSE AVENUE, Moldgreen, Huddersfield, HD59TZ">{{ isset($client) && isset($client->trading_address) ? $client->trading_address : '' }}</textarea>
                                            </div>
                                            <div class="col-lg-4">
                                                <label for=""> Registered Address Line 1</label>
                                                <textarea class="form-control my-2" id="address_line1" name="address_line1" placeholder="Ex: 34 WHITE ROSE AVENUE">{{ isset($client) && isset($client->address_line1) ? $client->address_line1 : '' }}</textarea>
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">Address Line 2</label>
                                                <textarea class="form-control my-2" id="address_line2" name="address_line2" placeholder="Ex: Moldgreen">{{ isset($client) && isset($client->address_line2) ? $client->address_line2 : '' }}</textarea>
                                            </div>
                                            <div class="col-lg-4 d-none">
                                                <label for="">Address Line 3</label>
                                                <textarea class="form-control my-2" id="address_line3" name="address_line3" placeholder="Enter address line 3">{{ isset($client) && isset($client->address_line3) ? $client->address_line3 : '' }}</textarea>
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">City </label>
                                                <input type="text" class="form-control my-2" id="city" name="city" value="{{ isset($client) && isset($client->city) ? $client->city : '' }}" placeholder="Ex: Huddersfield">
                                            </div>
                                            <div class="col-lg-4 d-none">
                                                <label for="">Town </label>
                                                <input type="text" class="form-control my-2" id="town" name="town" placeholder="Enter town" value="{{ isset($client) && isset($client->town) ? $client->town : '' }}">
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">Postal Code </label>
                                                <input type="text" class="form-control my-2" id="postcode" name="postcode" placeholder="Ex: HD59TZ" value="{{ isset($client) && isset($client->postcode) ? $client->postcode : '' }}">
                                            </div>
                                            <div class="col-lg-4 d-none">
                                                <label for="country">Country</label>
                                                <div class="mt-2">
                                                    <select class="form-control my-2" id="country" name="country">
                                                        <option value="" >Choose Country</option>
                                                        @isset($client)
                                                        <option value="UK" {{ isset($client->country) && $client->country == 'UK' ? 'selected' : '' }}>UK</option>
                                                        @endisset
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="" class="mb-2">Upload Photo Id </label>
                                                <div class="position-relative">
                                                    <input type="file" class="form-control" name="photo_id" id="photo_id"
                                                        accept="image/*" placeholder="Upload photo id">
                                                    <i class="bi bi-paperclip position-absolute top-50 translate-middle-y"
                                                        style="right: 8px;"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-4 mx-auto text-center">
                                                @if(isset($client->id))
                                                <button id="details-updateButton" class="btn btn-sm bg-theme text-light btn-outline-dark">Update</button>
                                                @else
                                                <button id="details-clearButton" class="btn btn-sm btn-outline-dark">Clear</button>
                                                <button id="details-saveButton" class="btn btn-sm bg-theme text-light btn-outline-dark"><span id="save-details-span">Save</span></button>
                                                @endif
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

    function scrollToTop() {
        $('html, body').animate({
            scrollTop: 0
        }, 'fast');
    }

    document.getElementById('pic').addEventListener('change', function(event) {
        var file = event.target.files[0];
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('imagePreview').src = e.target.result;
        };
        reader.readAsDataURL(file);
    });

    $(document).ready(function() {
        var clientId;
        $('#details-saveButton').click(function(event) {
            event.preventDefault();
            var saveButton = $(this);
            var saveDetailsSpan = $('#save-details-span');

            saveDetailsSpan.prepend('<i class="fa fa-spinner fa-spin"></i>');
            saveDetailsSpan.addClass('text-dark');
            saveButton.prop('disabled', true);

            var formData = new FormData($('#detailsForm')[0]);
            formData.append('name', $('#name').val());
            formData.append('client_type_id', $('#client_type_id').val());
            formData.append('manager_id', $('#manager_id').val());
            formData.append('reference_id', $('#reference_id').val());

            $.ajax({
                url: "{{URL::to('/admin/client')}}",
                type: 'POST',
                data: formData,
                async: false,
                cache: false,
                contentType: false,
                processData: false,
                success: function(response) {
                    saveButton.find('.fa-spinner').remove();
                    saveDetailsSpan.removeClass('text-dark');
                    saveButton.prop('disabled', false);
                    if (response.status === 200) {
                        toastr.success("Client details created successfully", "Success!");

                        var clientId = response.client_id;
                        window.setTimeout(function() {
                            window.location.href = "{{ route('client.update.form', ':id') }}".replace(':id', clientId);
                        }, 1000);
                    } else {
                        toastr.error(response.message[Object.keys(response.message)[0]], "Error");
                    }
                },
                error: function(xhr) {
                    saveButton.find('.fa-spinner').remove();
                    saveDetailsSpan.removeClass('text-dark');
                    saveButton.prop('disabled', false);

                    var errorMessage = "An error occurred. Please try again later.";
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        errorMessage = Object.values(xhr.responseJSON.errors)[0][0];
                    }
                    toastr.error(errorMessage, "Error");
                }
            });

            return false;
        });


        $('#details-clearButton').click(function() {
            event.preventDefault();
            $('#detailsForm')[0].reset();
        });
    });
</script>

@endsection