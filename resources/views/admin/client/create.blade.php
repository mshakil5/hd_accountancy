@extends('admin.layouts.admin')

@section('content')

<section class="section">
    <div class="row">
        <div class="col-lg-12 px-0 border shadow-sm">
            <p class="p-2 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center">
                <i class='bx bxs-user-plus fs-4 me-2'></i> New Client Entry
            </p>

            <!-- Success and Error message -->
            <div class="row my-4 px-3">
                <div class="col-lg-12">
                    <div id="successMessage" class="alert alert-success" style="display: none;">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                        <b></b>
                    </div>
                    <div id="errorMessage" class="alert alert-danger" style="display: none;">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                        <b></b>
                    </div>
                </div>
            </div>
            <!-- Success and Error message -->

            <!-- Top 3 -->
            <div class="row my-4 px-3">
                <div class="col-lg-3">
                    <label for="">Client Name</label>
                    <input for="myForm" type="text" class="form-control mt-2" name="name" id="name">
                </div>
                <div class="col-lg-3">
                    <label for="">Client Type</label>
                    <div class="mt-2">
                        <select name="client_type_id" class="form-control mt-2 select2" id="client_type_id">
                            <option value="" selected disabled>Select client</option>
                            @foreach($clientTypes as $clientType)
                                <option value="{{ $clientType->id }}">{{ $clientType->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-lg-3">
                    <label for="">Client Manager</label>
                        <div class="mt-2">
                            <select class="form-control mt-2 select2" name="manager_id" id="manager_id">
                                <option value=""  disabled>Select manager</option>
                                @foreach($managers as $manager)
                                    <option value="{{ $manager->id }}">{{ $manager->first_name }}</option>
                                @endforeach
                            </select>
                    </div>
                </div>
            </div>
            <!-- Top 3 -->

            <div class="row px-3">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <!-- Default Tabs -->
                            <ul class="nav nav-tabs mt-4 d-flex" id="myTabjustified" role="tablist">
                                <li class="nav-item flex-fill" role="presentation">
                                    <button class="nav-link w-100 active" id="home-tab" data-bs-toggle="tab" data-bs-target="#home" type="button" role="tab" aria-controls="home" aria-selected="true">Client Details</button>
                                </li>
                                <li class="nav-item flex-fill" role="presentation">
                                    <button  class="nav-link w-100" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="false">Business Info</button>
                                </li>
                                <li class="nav-item flex-fill" role="presentation">
                                    <button class="nav-link w-100" id="contact-tab" data-bs-toggle="tab" data-bs-target="#contact" type="button" role="tab" aria-controls="contact" aria-selected="false">Director Info</button>
                                </li>
                                <li class="nav-item flex-fill" role="presentation">
                                    <button class="nav-link w-100" id="service-tab" data-bs-toggle="tab" data-bs-target="#service" type="button" role="tab" aria-controls="service" aria-selected="false">Service list</button>
                                </li>
                                <li class="nav-item flex-fill" role="presentation">
                                    <button class="nav-link w-100" id="contact-info-tab" data-bs-toggle="tab" data-bs-target="#contact-info" type="button" role="tab" aria-controls="contact-info" aria-selected="false">Contact-info</button>
                                </li>
                                <li class="nav-item flex-fill" role="presentation">
                                    <button class="nav-link w-100" id="custom-field-tab" data-bs-toggle="tab" data-bs-target="#custom-field" type="button" role="tab" aria-controls="custom-field" aria-selected="false">Custom-field</button>
                                </li>
                                <li class="nav-item flex-fill" role="presentation">
                                    <button class="nav-link w-100" id="recent-update-tab" data-bs-toggle="tab" data-bs-target="#recent-update" type="button" role="tab" aria-controls="recent-update" aria-selected="false">Recent-update</button>
                                </li>
                            </ul>
                            <!-- Tabs end -->

                            <!-- All Form Start -->
                            <div class="tab-content pt-2" id="myTabjustifiedContent">
                                <!-- Client details form -->
                                <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
                                    <form id="detailsForm">
                                        <div class="row my-4">
                                            <div class="col-lg-4">
                                                <label for="">Email</label>
                                                <input type="email" class="form-control my-2" id="email" name="email" placeholder="Enter email">
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">Phone</label>
                                                <input type="number" class="form-control my-2" id="phone" name="phone" placeholder="Enter phone">
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">City</label>
                                                <input type="text" class="form-control my-2" id="city" name="city" placeholder="Enter city">
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">State</label>
                                                <input type="text" class="form-control my-2" id="state" name="state" placeholder="Enter state">
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">Postal Code</label>
                                                <input type="text" class="form-control my-2" id="postcode" name="postcode" placeholder="Enter postal code">
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="country">Country</label>
                                                <div class="mt-2">
                                                    <select class="form-control my-2" id="country" name="country">
                                                        <option value="" selected disabled>Choose Country</option>
                                                        <option value="Bangladesh">Bangladesh</option>
                                                        <option value="UK">UK</option>
                                                        <option value="USA">USA</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">Upload Photo/logo</label>
                                                <input type="file" class="form-control my-2" id="photo" name="photo">
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">Address 1</label>
                                                <textarea class="form-control my-2" id="address_line1" name="address_line1" placeholder="Enter address 1"></textarea>
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">Address 2</label>
                                                <textarea class="form-control my-2" id="address_line2" name="address_line2" placeholder="Enter address 2"></textarea>
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">Upload Photo ID</label>
                                                <input type="file" class="form-control my-2" id="photo_id" name="photo_id">
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
                                <!-- Client details -->
                            
                                <!-- Business info  -->
                                <div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                                    @include('admin.client.business')
                                </div>
                                <!-- Business info  -->

                                <!-- Director info -->
                                <div class="tab-pane fade" id="contact" role="tabpanel" aria-labelledby="contact-tab">
                                    @include('admin.client.director')
                                </div>
                                <!-- Director info -->

                                <!-- Service -->
                                <div class="tab-pane fade" id="service" role="tabpanel" aria-labelledby="service-tab">
                                    @include('admin.client.service')
                                </div>
                                <!-- Service -->

                                <!-- Contact Info -->
                                <div class="tab-pane fade" id="contact-info" role="tabpanel" aria-labelledby="contact-info-tab">
                                    @include('admin.client.contact')
                                </div>
                                <!-- Contact Info -->

                                <!-- Custom Field -->
                                <div class="tab-pane fade" id="custom-field" role="tabpanel" aria-labelledby="custom-field-tab">
                                custom-field
                                </div>
                                <!-- Custom Field -->

                                <!-- Recent Update -->
                                <div class="tab-pane fade" id="recent-update" role="tabpanel" aria-labelledby="recent-update-tab">
                                recent-update
                                </div>
                                <!-- Recent Update -->

                            </div>

                            <!-- All Form End -->
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
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
</script>

<!-- Selecting Client's Name, Client Type, Manager name when selects the client reference -->
<script>
    var clientsData = {!! $clients->toJson() !!};

    $(document).ready(function() {
        $('.select2').select2();

        $('#client_id').on('change click', function() {
            var selectedClientId = $(this).val();
            var clientData = clientsData.find(client => client.id == selectedClientId);

            if (clientData) {
                var clientTypeName = clientData.client_type.name;
                var managerName = clientData.manager.first_name;

                $('#name').val(clientData.name);
                $('#client_type_id').val(clientTypeName);
                $('#manager_id').val(managerName);
            } else {
                $('#name').val('');
                $('#client_type_id').val('');
                $('#manager_id').val('');
            }
        });
    });
</script>
<!-- Selecting Client's Name, Client Type, Manager name when selects the client reference -->

<!-- Client Details Start-->
<script>
    $(document).ready(function () {
        $('#details-saveButton').click(function (event) {
            event.preventDefault();
            
            var name = $('#name').val();
            var clientTypeId = $('#client_type_id').val();
            var managerId = $('#manager_id').val();

           var formData = new FormData($('#detailsForm')[0]);

            formData.append('name', name);
            formData.append('client_type_id', clientTypeId);
            formData.append('manager_id', managerId);

            $.ajax({
                url: "{{URL::to('/admin/client')}}",
                type: 'POST',
                data: formData,
                async: false,
                success: function (response) {
                    if (response.status === 200) {
                        $('#successMessage b').text(response.message);
                        $('#successMessage').show();
                        $('#errorMessage').hide();
                        $('#detailsForm')[0].reset();
                        $('#name').val('');
                        $('#client_type_id').val('');
                        $('#manager_id').val('');
                    } else {
                        $('#errorMessage b').text(response.message);
                        $('#errorMessage').show();
                        $('#successMessage').hide();
                        $('#detailsForm')[0].reset();
                    }
                },
                error: function (xhr, status, error) {
                    var errorMessage = "";
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            $.each(xhr.responseJSON.errors, function (key, value) {
                                errorMessage += key + ": " + value.join(", ") + "<br>";
                            });
                        } else {
                            errorMessage = "An error occurred. Please try again later.";
                        }
                        $('#errorMessage').html(errorMessage);
                        $('#errorMessage').show();
                        $('#successMessage').hide();
                },
                cache: false,
                contentType: false,
                processData: false
            });
            return false;
        });

        $('#details-clearButton').click(function () {
            event.preventDefault();
            $('#detailsForm')[0].reset();
        });
    });

</script>
<!-- Client Details End-->

<!-- Business Info Start -->
<script>
    $(document).ready(function () {
        $('#business-saveButton').click(function (event) {
            event.preventDefault();

           var formData = new FormData($('#businessForm')[0]);

            $.ajax({
                url: "{{URL::to('/admin/business-info')}}",
                type: 'POST',
                data: formData,
                async: false,
                success: function (response) {
                    if (response.status === 200) {
                        $('#successMessage b').text(response.message);
                        $('#successMessage').show();
                        $('#errorMessage').hide();
                        $('#businessForm')[0].reset();
                    } else {
                        $('#errorMessage b').text(response.message);
                        $('#errorMessage').show();
                        $('#successMessage').hide();
                        $('#businessForm')[0].reset();
                    }
                },
                error: function (xhr, status, error) {
                    var errorMessage = "";
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            $.each(xhr.responseJSON.errors, function (key, value) {
                                errorMessage += key + ": " + value.join(", ") + "<br>";
                            });
                        } else {
                            errorMessage = "An error occurred. Please try again later.";
                        }
                        $('#errorMessage').html(errorMessage);
                        $('#errorMessage').show();
                        $('#successMessage').hide();
                },
                cache: false,
                contentType: false,
                processData: false
            });
            return false;
        });

        $('#business-clearButton').click(function () {
            event.preventDefault();
            $('#businessForm')[0].reset();
        });
    });
</script>
<!-- Business Info End -->

<!-- Director Info Start -->
<script>
    $(document).ready(function () {
        $('#director-saveButton').click(function (event) {
            event.preventDefault();

           var formData = new FormData($('#directorForm')[0]);

            $.ajax({
                url: "{{URL::to('/admin/director-info')}}",
                type: 'POST',
                data: formData,
                async: false,
                success: function (response) {
                    if (response.status === 200) {
                        $('#successMessage b').text(response.message);
                        $('#successMessage').show();
                        $('#errorMessage').hide();
                        $('#directorForm')[0].reset();
                    } else {
                        $('#errorMessage b').text(response.message);
                        $('#errorMessage').show();
                        $('#successMessage').hide();
                        $('#directorForm')[0].reset();
                    }
                },
                error: function (xhr, status, error) {
                    var errorMessage = "";
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            $.each(xhr.responseJSON.errors, function (key, value) {
                                errorMessage += key + ": " + value.join(", ") + "<br>";
                            });
                        } else {
                            errorMessage = "An error occurred. Please try again later.";
                        }
                        $('#errorMessage').html(errorMessage);
                        $('#errorMessage').show();
                        $('#successMessage').hide();
                },
                cache: false,
                contentType: false,
                processData: false
            });
            return false;
        });

        $('#director-clearButton').click(function () {
            event.preventDefault();
            $('#directorForm')[0].reset();
        });
    });
</script>
<!-- Director Info End -->

<!-- Service Start -->
<script>
    $(document).ready(function () {
        $('#service-saveButton').click(function (event) {
            event.preventDefault();

            var formData = new FormData($('#serviceForm')[0]);

            $.ajax({
                url: "{{URL::to('/admin/service-assign')}}",
                type: 'POST',
                data: formData,
                async: false,
                success: function (response) {
                    if (response.status === 200) {
                        $('#successMessage b').text(response.message);
                        $('#successMessage').show();
                        $('#errorMessage').hide();
                        $('#serviceForm')[0].reset();
                    } else {
                        $('#errorMessage b').text(response.message);
                        $('#errorMessage').show();
                        $('#successMessage').hide();
                        $('#serviceForm')[0].reset();
                    }
                },
                error: function (xhr, status, error) {
                    var errorMessage = "";
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            $.each(xhr.responseJSON.errors, function (key, value) {
                                errorMessage += key + ": " + value.join(", ") + "<br>";
                            });
                        } else {
                            errorMessage = "An error occurred. Please try again later.";
                        }
                        $('#errorMessage').html(errorMessage);
                        $('#errorMessage').show();
                        $('#successMessage').hide();
                },
                cache: false,
                contentType: false,
                processData: false
            });
            return false;
        });

        $('#service-clearButton').click(function () {
            event.preventDefault();
            $('#serviceForm')[0].reset();
        });
    });
</script>
<!-- Service End -->

<!-- Add New Service and Fetch All Updated Services  -->
<script>
    $(document).ready(function () {
        function fetchAndRenderAllServices() {
            $.ajax({
                url: "/admin/all-services",
                type: 'GET',
                success: function (response) {
                    if (response.status === 200) {
                        $('.services-container').empty();
                        response.services.forEach(function (service) {
                            var serviceHtml = `
                                <div class="form-check form-check-inline" style="font-size: 1.2em;">
                                    <input class="form-check-input" type="checkbox" id="service_${service.id}" name="services[]" value="${service.id}">
                                    <label class="form-check-label ml-2" for="service_${service.id}">${service.name}</label>
                                </div>
                            `;
                            $('.services-container').append(serviceHtml);
                        });
                    } else {
                        $('#errorMessage b').text('Failed to fetch services. Please try again.');
                        $('#errorMessage').show();
                        $('#successMessage').hide();
                    }
                },
                error: function (xhr, status, error) {
                    var errorMessage = "An error occurred. Please try again later.";
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        errorMessage = "";
                        $.each(xhr.responseJSON.errors, function (key, value) {
                            errorMessage += key + ": " + value.join(", ") + "<br>";
                        });
                    }
                    $('#errorMessage').html(errorMessage);
                    $('#errorMessage').show();
                    $('#successMessage').hide();
                }
            });
        }

        fetchAndRenderAllServices();

        $('#addServiceButton').click(function (event) {
            event.preventDefault();

            var newServiceName = $('#new_service_name').val();

            if (newServiceName.trim() !== '') {
                $.ajax({
                    url: "/admin/create-specific-service",
                    type: 'POST',
                    data: {
                        name: newServiceName
                    },
                    success: function (response) {
                        if (response.status === 200) {
                            $('#new_service_name').val('');
                            $('#successMessage b').text('Service added successfully!');
                            $('#successMessage').show();
                            $('#errorMessage').hide();
                            fetchAndRenderAllServices();
                        } else {
                            $('#errorMessage b').text('Failed to add service. Please try again.');
                            $('#errorMessage').show();
                            $('#successMessage').hide();
                        }
                    },
                    error: function (xhr, status, error) {
                        var errorMessage = "An error occurred. Please try again later.";
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            errorMessage = "";
                            $.each(xhr.responseJSON.errors, function (key, value) {
                                errorMessage += key + ": " + value.join(", ") + "<br>";
                            });
                        }
                        $('#errorMessage').html(errorMessage);
                        $('#errorMessage').show();
                        $('#successMessage').hide();
                    }
                });
            } else {
                $('#errorMessage b').text('Please enter a valid service name.');
                $('#errorMessage').show();
                $('#successMessage').hide();
            }
        });
    });
</script>
<!-- Add New Service and Fetch All Updated Services End -->

<!-- Contact Info Start -->
<script>
    $(document).ready(function () {
        $('#contact-saveButton').click(function (event) {
            event.preventDefault();

            var formData = new FormData($('#contactForm')[0]);

            $.ajax({
                url: "{{URL::to('/admin/contact-info')}}",
                type: 'POST',
                data: formData,
                async: false,
                success: function (response) {
                    if (response.status === 200) {
                        $('#successMessage b').text(response.message);
                        $('#successMessage').show();
                        $('#errorMessage').hide();
                        $('#contactForm')[0].reset();
                    } else {
                        $('#errorMessage b').text(response.message);
                        $('#errorMessage').show();
                        $('#successMessage').hide();
                    }
                },
                error: function (xhr, status, error) {
                    var errorMessage = "";
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        $.each(xhr.responseJSON.errors, function (key, value) {
                            errorMessage += key + ": " + value.join(", ") + "<br>";
                        });
                    } else {
                        errorMessage = "An error occurred. Please try again later.";
                    }
                    $('#errorMessage').html(errorMessage);
                    $('#errorMessage').show();
                    $('#successMessage').hide();
                },
                cache: false,
                contentType: false,
                processData: false
            });
        });

        $('#contact-clearButton').click(function () {
            event.preventDefault();
            $('#contactForm')[0].reset();
        });
    });
</script>
<!-- Contact Info End -->

@endsection