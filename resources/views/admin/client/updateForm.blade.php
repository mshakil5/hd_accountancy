@extends('admin.layouts.admin')

@section('content')

<section class="section">
    <div class="row">
        <div class="col-lg-12 px-0 shadow-sm border-theme border-2">
            <p class="p-2 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center">
                <i class='bx bxs-user-plus fs-4 me-2'></i> Client Update
            </p>

            <div class="row my-4 px-3">
                <div class="col-lg-3">
                    <label for="">Client Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control my-2" id="name" value="{{ $client->name }}" name="name">
                </div>

                <div class="col-lg-3">
                    <label for="">Last Name <span class="text-danger">*</span></label>
                    <input for="last_name" type="text" value="{{ isset($client->last_name) ? $client->last_name : '' }}" class="form-control mt-2" name="last_name" id="client_last_name" required placeholder="">
                </div>

                <div class="col-lg-2">
                    <label for="">Reference ID <span class="text-danger">*</span></label>
                    <input type="text" class="form-control my-2" id="reference_id" name="reference_id" placeholder="Ex: LT-001" value="{{ isset($client) && isset($client->refid) ? $client->refid : '' }}">
                </div>

                <div class="col-lg-2">
                    <label for="country">Client Type <span class="text-danger">*</span> </label>
                    <div class="mt-2">
                        <select class="form-control my-2" id="client_type_id" name="client_type_id">
                            <option value="">Please select</option>
                            @foreach($clientTypes as $clientType)
                            <option value="{{ $clientType->id }}" {{ $client->client_type_id == $clientType->id ? 'selected' : '' }}>{{ $clientType->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-lg-2">
                    <label for="">Client Manager</label>
                    <div class="mt-2">
                        <select class="form-control my-2" name="manager_id" id="manager_id">
                            <option value="">Please select</option>
                            @foreach($managers as $manager)
                            <option value="{{ $manager->id }}" data-id-number="{{ $manager->id_number }}" {{ $client->manager_id == $manager->id ? 'selected' : '' }}>{{ $manager->first_name }} {{ $manager->last_name }} ({{ $manager->type }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

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
                                    <button class="nav-link w-100" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="false">Business Info</button>
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
                                    <button class="nav-link w-100" id="recent-update-tab" data-bs-toggle="tab" data-bs-target="#recent-update" type="button" role="tab" aria-controls="recent-update" aria-selected="false">Recent-update</button>
                                </li>
                                <li class="nav-item flex-fill" role="presentation">
                                    <button class="nav-link w-100" id="about-business-tab" data-bs-toggle="tab" data-bs-target="#about-business" type="button" role="tab" aria-controls="about-business" aria-selected="false">About Business</button>
                                </li>
                                <li class="nav-item flex-fill" role="presentation">
                                    <button class="nav-link w-100" id="accountancy-tab" data-bs-toggle="tab" data-bs-target="#accountancy" type="button" role="tab" aria-controls="accountancy" aria-selected="false">Accountancy Fees</button>
                                </li>
                            </ul>
                            <!-- Tabs end -->

                            <!-- Form Start -->
                            <div class="tab-content pt-2" id="myTabjustifiedContent">
                                <!-- Client details form -->
                                <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
                                    @include('admin.client.details')
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

                                <!-- Recent Update -->
                                <div class="tab-pane fade" id="recent-update" role="tabpanel" aria-labelledby="recent-update-tab">
                                    @include('admin.client.recent_update')
                                </div>
                                <!-- Recent Update -->

                                <!-- About Business-->
                                <div class="tab-pane fade" id="about-business" role="tabpanel" aria-labelledby="about-business-tab">
                                    @include('admin.client.about_business')
                                </div>
                                <!-- About Business -->

                                <!-- Accountancy-->
                                <div class="tab-pane fade" id="accountancy" role="tabpanel" aria-labelledby="accountancy-tab">
                                    @include('admin.client.accountancy')
                                </div>
                                <!-- About Business -->

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
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
</script>

<!-- Image preview start -->
<script>
    $('#pic').on('change', function(event) {
        var file = event.target.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#imagePreview').attr('src', e.target.result);
            };
            reader.readAsDataURL(file);
        }
    });
</script>
<!-- Image preview end -->

<!-- Client details update -->
<script>

$(document).on('click', '.toggle-password', function () {
      const target = $(this).data('target');
      const input = $(target);
      const icon = $(this).find('i');
      
      if (input.attr('type') === 'password') {
          input.attr('type', 'text');
          icon.removeClass('fa-eye').addClass('fa-eye-slash');
      } else {
          input.attr('type', 'password');
          icon.removeClass('fa-eye-slash').addClass('fa-eye');
      }
    });

    function toggleClientFields() {
        var selectedId = $('#client_type_id').val();

        $('#client-type-9-fields, #client-type-8-fields').hide().find('input, select').val('');

        if (selectedId == '9') {
            $('#client-type-9-fields').show();
        } else if (selectedId == '8') {
            $('#client-type-8-fields').show();
        }
    }

    $('#client_type_id').change(toggleClientFields);

    $(document).on('click', '.toggle-password', function () {
      const target = $(this).data('target');
      const input = $(target);
      const icon = $(this).find('i');
      
      if (input.attr('type') === 'password') {
          input.attr('type', 'text');
          icon.removeClass('fa-eye').addClass('fa-eye-slash');
      } else {
          input.attr('type', 'password');
          icon.removeClass('fa-eye-slash').addClass('fa-eye');
      }
    });

    $(document).ready(function() {
        $('#details-saveButton').click(function(event) {
            event.preventDefault();

            var name = $('#name').val();
            var clientTypeId = $('#client_type_id').val();
            var managerId = $('#manager_id').val();

            var formData = new FormData($('#detailsForm')[0]);
            var clientId = "{{ $client->id ?? '' }}";

            formData.append('name', $('#name').val());
            formData.append('last_name', $('#client_last_name').val());
            formData.append('client_type_id', $('#client_type_id').val());
            formData.append('manager_id', $('#manager_id').val());
            formData.append('reference_id', $('#reference_id').val());

            // for (var pair of formData.entries()) {
            //     console.log(pair[0] + ': ' + pair[1]);
            // }

            if (clientId) {
                $.ajax({
                    url: "/admin/client-details-update/" + clientId,
                    type: 'POST',
                    data: formData,
                    async: false,
                    success: function(response) {
                        toastr.success("Client details updated successfully", "Success!");

                    },
                    error: function(xhr, status, error) {
                        console.error(xhr.responseText);
                        var errorMessage = "An error occurred. Please try again later.";
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            errorMessage = Object.values(xhr.responseJSON.errors)[0][0];
                        }
                        toastr.error(errorMessage, "Error");
                    },
                    cache: false,
                    contentType: false,
                    processData: false
                });
            }
            return false;
        });
    });
</script>
<!-- Client Details update End-->

<!-- Businessinfo update -->
<script>
    $(document).ready(function() {
        $('#business-saveButton').click(function(event) {
            event.preventDefault();

            var formData = new FormData($('#businessForm')[0]);
            var clientId = "{{ $client->id ?? '' }}";

            if (clientId) {
                $.ajax({
                    url: "/admin/client-businessinfo-update/" + clientId,
                    type: 'POST',
                    data: formData,
                    async: false,
                    success: function(response) {
                        toastr.success("Business Info updated successfully", "Success!");
                    },
                    error: function(xhr, status, error) {
                        console.error(xhr.responseText);
                        var errorMessage = "An error occurred. Please try again later.";
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            errorMessage = Object.values(xhr.responseJSON.errors)[0][0];
                        }
                        toastr.error(errorMessage, "Error");

                    },
                    cache: false,
                    contentType: false,
                    processData: false
                });
            }
            return false;
        });
    });
</script>
<!-- Businessinfo update End-->

<!-- Populating director info and update start-->
<script>
    $(document).ready(function() {
        $('#createNewButton').click(function(event) {
            event.preventDefault();
            $('#directorFormContainer').toggle();
            $('html, body').animate({
                scrollTop: $('#directorFormContainer').offset().top - 200
            }, 500);
        });

        $('#directorTable').on('click', '.edit-director', function(event) {
            event.preventDefault();
            var directorInfo = JSON.parse($(this).closest('tr').attr('data-director-info'));

            $('#dir-name').val(directorInfo.name);
            $('#dir-last-name').val(directorInfo.last_name);
            $('#dir-phone').val(directorInfo.phone);
            $('#dir-email').val(directorInfo.email);
            $('#address').val(directorInfo.address);
            $('#dob').val(directorInfo.dob);
            $('#ni_number').val(directorInfo.ni_number);
            $('#utr_number').val(directorInfo.utr_number);
            $('#utr_authorization').val(directorInfo.utr_authorization);
            $('#directors_tax_return').val(directorInfo.directors_tax_return);
            $('#hmrc_authorisation').val(directorInfo.hmrc_authorisation);
            $('#nino').val(directorInfo.nino);

            $('#directorIdInput').val(directorInfo.id);

            $('#director-saveButton, #director-clearButton').hide();
            $('#director-updateButton, #director-cancelButton').show();
            $('#directorFormContainer').show();

            $('html, body').animate({
                scrollTop: $('#directorForm').offset().top - 200
            }, 500);
        });

        $('#director-cancelButton').click(function(event) {
            event.preventDefault();
            $('#directorForm input').val('');
            $('#director-updateButton').hide();
            $('#director-saveButton, #director-clearButton').show();
            $('#directorFormContainer').hide();
            $('html, body').animate({
                scrollTop: 0
            }, 500);
        });

        $('#directorForm').submit(function(event) {
            event.preventDefault();
            var directorId = $('#directorIdInput').val();

            var formData = new FormData($(this)[0]);

            $.ajax({
                url: "/admin/client-directorinfo-update/" + directorId,
                type: 'POST',
                data: formData,
                success: function(response) {
                    toastr.success("Director Info updated successfully", "Success!");

                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                },
                error: function(xhr, status, error) {
                    var errorMessage = "An error occurred. Please try again later.";
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        errorMessage = Object.values(xhr.responseJSON.errors)[0][0];
                    }
                    toastr.error(errorMessage, "Error");

                },
                cache: false,
                contentType: false,
                processData: false
            });

            return false;
        });
    });
</script>
<!-- Populating director info and update end-->

<!-- Delete director start-->
<script>
    $(document).ready(function() {
        $('#directorTable').on('click', '.delete-director', function() {
            var directorId = $(this).closest('tr').data('director-id');

            if (confirm("Are you sure you want to delete this director?")) {
                $.ajax({
                    url: '/admin/delete-director/' + directorId,
                    type: 'DELETE',
                    success: function(response) {
                        toastr.success("Director Info deleted successfully", "Success!");

                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    },
                    error: function(xhr, status, error) {
                        var errorMessage = "An error occurred. Please try again later.";
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            errorMessage = Object.values(xhr.responseJSON.errors)[0][0];
                        }
                        toastr.error(errorMessage, "Error");
                    },
                    cache: false,
                    contentType: false,
                    processData: false
                });
            }
        });
    });
</script>
<!-- Delete director end-->

<!-- Director Info create Start -->
<script>
    $(document).ready(function() {
        $('#director-saveButton').click(function(event) {
            event.preventDefault();
            var clientId = "{{ $client->id ?? '' }}";

            var formData = new FormData($('#directorForm')[0]);
            formData.append('client_id', clientId);

            $.ajax({
                url: "{{URL::to('/admin/director-info')}}",
                type: 'POST',
                data: formData,
                async: false,
                success: function(response) {
                    if (response.status === 200) {
                        toastr.success("Director Info created successfully", "Success!");

                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    }
                },
                error: function(xhr, status, error) {
                    var errorMessage = "An error occurred. Please try again later.";
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        errorMessage = Object.values(xhr.responseJSON.errors)[0][0];
                    }
                    toastr.error(errorMessage, "Error");

                },
                cache: false,
                contentType: false,
                processData: false
            });
            return false;
        });

        $('#director-clearButton').click(function() {
            event.preventDefault();
            $('#directorForm')[0].reset();
        });
    });
</script>
<!-- Director Info create End -->

<!-- Fetching sub services and putting on table start -->
<script>
    $(document).ready(function() {
        $('#serviceDropdown').change(function() {
            var selectedOption = $(this).find(':selected');
            var serviceDataId = selectedOption.data('service-id');
            var serviceId = $(this).val();
            if (serviceId) {

                // var exists = false;

                // $('.subServiceDetails').each(function() {
                //     if ($(this).find('input[name="service_id"]').val() == serviceId) {
                //         exists = true;
                //         return false;
                //     }
                // });
                // if (exists) {
                //     alert('This service is already added.');
                //     return;
                // }

                $.ajax({
                    url: '/admin/getSubServices/' + serviceId,
                    type: "GET",
                    dataType: "json",
                    success: function(data) {
                        var serviceName = $('#serviceDropdown option:selected').text();
                        var subServiceDetailsTemplate = `
                            <div class="row mt-4 subServiceDetails">
                                <div class="col-12">
                                    <p class="p-2 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center">
                                        ${serviceName}
                                        ${serviceDataId == 1 ? `
                                        <select class="form-select ms-auto directorDropdown" name="director_id" style="max-width: 200px;">
                                            <option value="">Select Director</option>
                                             @foreach($directorInfos as $director)
                                             <option value="{{ $director->id }}">{{ $director->name }}</option>
                                             @endforeach
                                        </select>
                                        <input type="hidden" name="service_data_id" value="${serviceDataId}">
                                        ` : ''}
                                    </p>
                                    
                                    <div class="border-theme p-3 border-1">
                                        <div class="row mt-2">
                                        <!-- Sub-service details -->
                                        </div>
                                        <table class="table mt-3">
                                        <thead>
                                            <tr>
                                            <th>Sub Service</th>
                                            <th>Deadline</th>
                                            <th>Staff</th>
                                            <th>Note</th>
                                            <th style="text-align: center;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Sub-service rows  -->
                                        </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            `;

                        $('#serviceForm').append(subServiceDetailsTemplate);

                        var serviceFields = `
                            <div class="row">
                                <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-3 text-center">
                                        <h5>Manager</h5>
                                        <div class="form-check">
                                            <input type="hidden" name="service_id" value="${serviceId}">
                                            <input type="hidden" name="client_service_id[]" value="">
                                            <select class="form-control mt-2 managerDropdown" name="manager_id">
                                            <option value="">Select</option>
                                            @foreach($managers as $manager)
                                            <option value="{{ $manager->id }}">{{ $manager->first_name }} {{ $manager->last_name }} ({{ $manager->type }})</option>
                                            @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2 text-center">
                                        <h5>Frequency</h5>
                                        <div class="form-check">
                                            <select class="form-control mt-2 serviceFrequency" id="serviceFrequency" name="service_frequency">
                                            <option value="">Select Frequency</option>
                                            <option>Weekly</option>
                                            <option>2 Weekly</option>
                                            <option>4 Weekly</option>
                                            <option>Monthly</option>
                                            <option>Quarterly</option>
                                            <option>Annually</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2 text-center">
                                        <h5>Due Date</h5>
                                        <div class="form-check">
                                            <input type="text" class="form-control dueDate" id="dueDate" name="dueDate">
                                        </div>
                                    </div>
                                    <div class="col-md-2 text-center">
                                        <h5>Target Deadline</h5>
                                        <div class="form-check">
                                            <input type="text" class="form-control legalDeadline" id="legalDeadline" name="legalDeadline">
                                        </div>
                                    </div>
                                    <div class="col-md-2 text-center">
                                        <h5>Deadline</h5>
                                        <div class="form-check">
                                            <input type="text" class="form-control serviceDeadline" id="serviceDeadline" name="service_deadline">
                                        </div>
                                    </div>
                                    <div class="col-md-1 text-center">
                                        <h5>Action</h5>
                                        <span class="removeSubServiceDetails" style="cursor: pointer; font-size: 24px; color: red;">&#10006;</span>
                                    </div>
                                </div>
                                </div>
                            </div>
                            `;
                        $('.subServiceDetails:last').find('.row:first').after(serviceFields);

                        $('.subServiceDetails:last').find('tbody').empty();
                        $.each(data, function(key, value) {
                            var newRow = `
                                    <tr>
                                    <td>${value.name}</td>
                                    <td><input type="text" id="deadline" name="deadline" class="form-control subServiceDeadline"></td>
                                    <td>
                                        <select class="form-control staffDropdown" id="selectedStaff" name="staff_id">
                                        <option value="">Select Staff</option>
                                        @foreach($staffs as $staff)
                                        <option value="{{ $staff->id }}">{{ $staff->first_name }} {{ $staff->last_name }} ({{ $staff->type }})</option>
                                        @endforeach
                                        </select>
                                    </td>
                                    <td><textarea name="note" id="note" rows="1" class="form-control" placeholder="Note for this task"></textarea></td>
                                    <td style="text-align: center;">
                                        <span class="removeSubServiceRow" style="cursor: pointer; font-size: 24px; color: red;">&#10006;</span>
                                    </td>
                                    <input type="hidden" class="sub-service-id" data-sub-service-id="${value.id}">
                                    <input type="hidden" name="sub_service_id[]" value="${value.id}">
                                    <input type="hidden" name="client_sub_service_id[]" value="">
                                    </tr>
                                `;
                            $('.subServiceDetails:last').find('tbody').append(newRow);

                            $('.subServiceDetails:last').find('.subServiceDeadline').datepicker({
                                format: 'dd-mm-yyyy',
                                autoclose: true,
                                todayHighlight: true
                            });

                            $('.dueDate, .legalDeadline, .serviceDeadline').datepicker({
                                format: 'dd-mm-yyyy',
                                autoclose: true,
                                todayHighlight: true
                            });

                        });
                    },
                    error: function(xhr, status, error) {
                        console.error(xhr.responseText);
                    }
                });
            }
        });

        $(document).on('click', '.removeSubServiceRow', function() {
            $(this).closest('tr').remove();
        });

        $(document).on('click', '.removeSubServiceDetails', function() {
            $(this).closest('.subServiceDetails').remove();
        });
    });
</script>
<!-- Fetching sub services and putting on table end -->

<!-- Storing services and sub services start -->
<script>
    $(document).ready(function() {
        $('#service-saveButton').click(function(e) {
            e.preventDefault();

            var clientId = "{{ $client->id ?? '' }}";
            var services = [];
            $('.subServiceDetails').each(function() {
                var serviceId = $('#serviceDropdown').val();

                var managerId = $(this).find('.managerDropdown').val();
                var service_frequency = $(this).find('.serviceFrequency').val();
                var service_deadline = $(this).find('.serviceDeadline').val();
                var due_date = $(this).find('.dueDate').val();
                var legal_deadline = $(this).find('.legalDeadline').val();
                var directorId = $(this).find('.directorDropdown').val();
                var serviceDataId = $(this).closest('.subServiceDetails').find('input[name="service_data_id"]').val();
                var subServices = [];

                $(this).find('tbody tr').each(function() {
                    var subServiceId = $(this).find('.sub-service-id').attr('data-sub-service-id');
                    var deadline = $(this).find('#deadline').val();
                    var note = $(this).find('textarea').val();
                    var staffId = $(this).find('.staffDropdown').val();

                    subServices.push({
                        subServiceId: subServiceId,
                        deadline: deadline,
                        note: note,
                        staffId: staffId
                    });
                });

                services.push({
                    serviceId: serviceId,
                    managerId: managerId,
                    service_frequency: service_frequency,
                    service_deadline: service_deadline,
                    subServices: subServices,
                    due_date: due_date,
                    legal_deadline: legal_deadline,
                    director_info_id: directorId,
                    service_data_Id: serviceDataId
                });
            });

            var data = {
                clientId: clientId,
                services: services
            };
            // console.log(data);

            $.ajax({
                url: '/admin/store-service',
                type: 'POST',
                data: data,
                success: function(response) {
                    // console.log(response);
                    toastr.success("Task assigned successfully", "Success!");

                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                },
                error: function(xhr, status, error) {
                    console.log(xhr.responseText);
                    var errorMessage = "An error occurred. Please try again later.";
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        errorMessage = Object.values(xhr.responseJSON.errors)[0][0];
                    }
                    toastr.error(errorMessage, "Error");

                },
            });
        });
    });
</script>
<!-- Storing services and sub services end -->

<!-- Updating services and sub services start -->
<script>
    $(document).ready(function() {
        $(document).on('click', '#service-updateButton', function(e) {
            e.preventDefault();

            var clientId = "{{ $client->id ?? '' }}";
            var services = [];

            $('.subServiceDetails').each(function() {
                var serviceId = $(this).find('input[name="service_id"]').val();
                var clientServiceId = $(this).find('input[name="client_service_id[]"]').val();
                var managerId = $(this).find('.managerDropdown').val();
                var directorId = $(this).find('.directorDropdown').val();
                var service_frequency = $(this).find('#serviceFrequency').val();
                var service_deadline = $(this).find('#serviceDeadline').val();
                var due_date = $(this).find('#dueDate').val();
                var legal_deadline = $(this).find('#legalDeadline').val();
                var serviceDataId = $(this).closest('.subServiceDetails').find('input[name="service_data_id"]').val();
                var subServices = [];

                $(this).find('tbody tr').each(function() {
                    var subServiceId = $(this).find('input[name="sub_service_id[]"]').val();
                    var clientSubServiceId = $(this).find('input[name="client_sub_service_id[]"]').val();
                    var deadline = $(this).find('#deadline').val();
                    var note = $(this).find('#note').val();
                    var staffId = $(this).find('#selectedStaff').val();

                    subServices.push({
                        subServiceId: subServiceId,
                        client_sub_service_id: clientSubServiceId,
                        deadline: deadline,
                        note: note,
                        staffId: staffId
                    });
                });

                services.push({
                    serviceId: serviceId,
                    client_service_id: clientServiceId,
                    managerId: managerId,
                    director_info_id: directorId,
                    service_data_Id: serviceDataId,
                    service_frequency: service_frequency,
                    service_deadline: service_deadline,
                    due_date: due_date,
                    legal_deadline: legal_deadline,
                    subServices: subServices
                });
            });

            var data = {
                clientId: clientId,
                services: services
            };

            // console.log(data);

            $.ajax({
                url: '/admin/update-service',
                type: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json',
                success: function(response) {
                    toastr.success("Tasks updated successfully", "Success!");

                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                },
                error: function(xhr, status, error) {
                    console.log(xhr.responseText);
                    var errorMessage = "An error occurred. Please try again later.";
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        errorMessage = Object.values(xhr.responseJSON.errors)[0][0];
                    }
                    toastr.error(errorMessage, "Error");
                },
            });
        });
    });
</script>
<!-- Updating services and sub services end -->

<!-- Contact Info create Start -->
<script>
    $(document).ready(function() {
        $('#contact-saveButton').click(function(event) {
            event.preventDefault();
            var clientId = "{{ $client->id ?? '' }}";

            var formData = new FormData($('#contactForm')[0]);
            formData.append('client_id', clientId);

            $.ajax({
                url: "{{URL::to('/admin/contact-info')}}",
                type: 'POST',
                data: formData,
                async: false,
                success: function(response) {
                    if (response.status === 200) {
                        toastr.success("Contact Info created successfully", "Success!");

                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    }
                },
                error: function(xhr, status, error) {
                    var errorMessage = "An error occurred. Please try again later.";
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        errorMessage = Object.values(xhr.responseJSON.errors)[0][0];
                    }
                    toastr.error(errorMessage, "Error");
                },
                cache: false,
                contentType: false,
                processData: false
            });
        });

        $('#contact-clearButton').click(function() {
            event.preventDefault();
            $('#contactForm')[0].reset();
        });
    });
</script>
<!-- Contact Info create End -->

<!-- Populating contact info and update start-->
<script>
    $(document).ready(function() {
        $('#createNewButton1').click(function(event) {
            event.preventDefault();
            $('#contactFormContainer').toggle();
            $('html, body').animate({
                scrollTop: $('#contactFormContainer').offset().top - 200
            }, 500);
        });

        $('#contactTable').on('click', '.edit-contact', function(event) {
            event.preventDefault();
            var contactInfo = JSON.parse($(this).closest('tr').attr('data-contact-info'));

            $('#greeting').val(contactInfo.greeting);
            $('#first_name').val(contactInfo.first_name);
            $('#last_name').val(contactInfo.last_name);
            $('#job_title').val(contactInfo.job_title);
            $('#contact-email').val(contactInfo.email);
            $('#contact-phone').val(contactInfo.phone);
            $('#company').val(contactInfo.company);

            $('#contactIdInput').val(contactInfo.id);

            $('#contact-saveButton, #contact-clearButton').hide();
            $('#contact-updateButton, #contact-cancelButton').show();
            $('#contactFormContainer').show();

            $('html, body').animate({
                scrollTop: $('#contactForm').offset().top - 200
            }, 500);
        });

        $('#contact-cancelButton').click(function(event) {
            event.preventDefault();
            $('#contactForm input').val('');
            $('#contact-updateButton').hide();
            $('#contact-saveButton, #contact-clearButton').show();
            $('#contactFormContainer').hide();
            $('html, body').animate({
                scrollTop: 0
            }, 500);
        });

        $('#contactForm').submit(function(event) {
            event.preventDefault();
            var contactId = $('#contactIdInput').val();

            var formData = new FormData($(this)[0]);

            $.ajax({
                url: "/admin/client-contactinfo-update/" + contactId,
                type: 'POST',
                data: formData,
                success: function(response) {
                    toastr.success("Contact Info updated successfully", "Success!");

                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                },
                error: function(xhr, status, error) {
                    var errorMessage = "An error occurred. Please try again later.";
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        errorMessage = Object.values(xhr.responseJSON.errors)[0][0];
                    }
                    toastr.error(errorMessage, "Error");
                },
                cache: false,
                contentType: false,
                processData: false
            });

            return false;
        });
    });
</script>
<!-- Populating contact info and update end-->

<!-- Delete contact start-->
<script>
    $(document).ready(function() {
        $('#contactTable').on('click', '.delete-contact', function() {
            var contactId = $(this).closest('tr').data('contact-id');

            if (confirm("Are you sure you want to delete this conatct?")) {
                $.ajax({
                    url: '/admin/delete-contact/' + contactId,
                    type: 'DELETE',
                    success: function(response) {
                        toastr.success("Contact Info deleted successfully", "Success!");

                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    },
                    error: function(xhr, status, error) {
                        var errorMessage = "An error occurred. Please try again later.";
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            errorMessage = Object.values(xhr.responseJSON.errors)[0][0];
                        }
                        toastr.error(errorMessage, "Error");

                    },
                    cache: false,
                    contentType: false,
                    processData: false
                });
            }
        });
    });
</script>
<!-- Delete contact end-->

<!-- Recent Update Start -->
<script>
    $(document).ready(function () {
        $('#createNewButton2').click(function (event) {
            event.preventDefault();
            $('#recentUpdateFormContainer').toggle();
            $('#recentUpdateForm')[0].reset();
            $('#recentUpdate-saveButton').show();
            $('#recentUpdate-updateButton').hide();
            $('html, body').animate({
                scrollTop: $('#recentUpdateFormContainer').offset().top - 200
            }, 500);
        });

        $('#recentUpdate-saveButton').click(function (event) {
            event.preventDefault();

            var formData = new FormData($('#recentUpdateForm')[0]);

            $.ajax({
                url: "{{ route('recent-updates.store') }}", 
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    toastr.success(response.message, "Success");
                    setTimeout(() => location.reload(), 2000);
                },
                error: function (xhr) {
                    toastr.error(xhr.responseJSON.message || "An error occurred.", "Error");
                }
            });
        });

        $('#recentUpdateTable').on('click', '.edit-recent-update', function () {
            var update = $(this).closest('tr').data('recent-update');
            $('#recentUpdateIdInput').val(update.id);
            $('#note').val(update.note);
            $('#recentUpdate-saveButton').hide();
            $('#recentUpdate-updateButton').show();
            $('#recentUpdateFormContainer').show();
            $('#recentUpdate-clearButton').hide();
            $('html, body').animate({
                scrollTop: $('#recentUpdateFormContainer').offset().top - 200
            }, 500);
        });

        $('#recentUpdate-updateButton').click(function (event) {
            event.preventDefault();
            var id = $('#recentUpdateIdInput').val();
            var formData = new FormData($('#recentUpdateForm')[0]);

            $.ajax({
                url: `{{ url('/admin/recent-updates') }}/${id}`,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    toastr.success(response.message, "Success");
                    setTimeout(() => location.reload(), 2000);
                },
                error: function (xhr) {
                    toastr.error(xhr.responseJSON.message || "An error occurred.", "Error");
                }
            });
        });

        $('#recentUpdate-cancelButton').click(function () {
            $('#recentUpdateFormContainer').hide();
            $('#recentUpdate-clearButton').show();
            $('#recentUpdateForm')[0].reset();
            $('#recentUpdate-saveButton').show();
            $('#recentUpdate-updateButton').hide();
            $('html, body').animate({
                scrollTop: $('#recentUpdateFormContainer').offset().top - 200
            }, 500);
        });

        $('#recentUpdate-clearButton').click(function () {
            event.preventDefault();
            $('#recentUpdateForm')[0].reset();
            $('#recentUpdate-saveButton').show();
            $('#recentUpdate-updateButton').hide();
        });

        $('#recentUpdateTable').on('click', '.delete-recent-update', function () {
            var update = $(this).closest('tr').data('recent-update');
            if (confirm("Are you sure? Once deleted, you will not be able to recover this update!")) {
                $.ajax({
                    url: `{{ url('/admin/recent-updates') }}/${update.id}`,
                    type: 'DELETE',
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    success: function (response) {
                        toastr.success(response.message, "Success");
                        setTimeout(() => location.reload(), 2000);
                    },
                    error: function (xhr) {
                        toastr.error(xhr.responseJSON.message || "An error occurred.", "Error");
                    }
                });
            }
        });
    });
</script>
<!-- Recent Update End -->
 
<!-- Data table initialize -->
<script>
    $(document).ready(function() {
        $("#directorTable, #contactTable, #recentUpdateTable").DataTable({});
    });
</script>
<!-- Data table initialize -->

<script>
    $('.dueDate, .legalDeadline, .serviceDeadline, .subServiceDeadline').datepicker({
        format: 'dd-mm-yyyy',
        autoclose: true,
        todayHighlight: true
    });
</script>

<script>
$(document).ready(function() {
    $('.summernote').summernote({
        height: 300, 
        toolbar: [
            ['style', ['bold', 'italic', 'underline', 'clear']],
            ['font', ['strikethrough', 'superscript', 'subscript']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['view', ['codeview', 'help']]
        ]
    });

    $('#aboutBusiness-updateButton').on('click', function(e) {
        e.preventDefault();

        let aboutBusinessData = {
            about_business: $('#about_business').val(),
            id: '{{ $client->id ?? '' }}',
            _token: '{{ csrf_token() }}'
        };

        $.ajax({
            url: '{{ route("aboutBusiness.update") }}',
            type: 'POST',
            data: aboutBusinessData,
            success: function(response) {
                toastr.success(response.message, "Success");
            },
            error: function(xhr, status, error) {
                toastr.error(xhr.responseJSON.message || "An error occurred.", "Error");
                console.error(xhr.responseText);
            }
        });
    });

    $('#accountancyForm').on('submit', function(e) {
        e.preventDefault();

        let formData = new FormData(this);

        $.ajax({
            url: '{{ route("accountancy-fee.storeOrUpdate") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                toastr.success(response.message, "Success");
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON.message || "An error occurred.", "Error");
                console.error(xhr.responseText);
            }
        });
    });
});
</script>

@endsection