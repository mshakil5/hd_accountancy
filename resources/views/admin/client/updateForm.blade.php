@extends('admin.layouts.admin')

@section('content')

<section class="section">
    <div class="row">
        <div class="col-lg-12 px-0 shadow-sm border-theme border-2">
            <p class="p-2 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center">
                <i class='bx bxs-user-plus fs-4 me-2'></i> Client Update
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

             <div class="row my-4 px-3">
                <div class="col-lg-3">
                    <label for="">Client Name</label>
                    <input type="text" class="form-control my-2" id="name" value="{{ $client->name }}"name="name">
                </div>

                <div class="col-lg-3">
                    <label for="country">Clien Type</label>
                    <div class="mt-2">
                        <select class="form-control select2 my-2" id="client_type_id" name="client_type_id">
                            <option value="" selected disabled>Choose Client Type</option>
                            @foreach($clientTypes as $clientType)
                                <option value="{{ $clientType->id }}" {{ $client->client_type_id == $clientType->id ? 'selected' : '' }}>{{ $clientType->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-lg-3">
                    <label for="">Client Manager</label>
                    <div class="mt-2">
                        <select class="form-control select2 my-2" name="manager_id" id="manager_id">
                            <option value="">Please select</option>
                            @foreach($managers as $manager)
                                <option value="{{ $manager->id }}" data-id-number="{{ $manager->id_number }}" {{ $client->manager_id == $manager->id ? 'selected' : '' }}>{{ $manager->first_name }} {{ $manager->last_name }}</option>
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
                                <li class="nav-item flex-fill d-none" role="presentation">
                                    <button class="nav-link w-100" id="custom-field-tab" data-bs-toggle="tab" data-bs-target="#custom-field" type="button" role="tab" aria-controls="custom-field" aria-selected="false">Custom-field</button>
                                </li>
                                <li class="nav-item flex-fill" role="presentation">
                                    <button class="nav-link w-100" id="recent-update-tab" data-bs-toggle="tab" data-bs-target="#recent-update" type="button" role="tab" aria-controls="recent-update" aria-selected="false">Recent-update</button>
                                </li>
                            </ul>
                            <!-- Tabs end -->

                            <!-- Form Start -->
                            <div class="tab-content pt-2" id="myTabjustifiedContent">
                                <!-- Client details form -->
                                <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
                                    <form id="detailsForm">
                                        <div class="row my-4">
                                            <div class="col-lg-9">
                                            </div>
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
                                            <div class="col-lg-4">
                                                <label for="">Email</label>
                                                <input type="email" class="form-control my-2" id="email" name="email" placeholder="Enter email" value="{{ $client->email }}">
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">Phone</label>
                                                <input type="number" class="form-control my-2" id="phone" name="phone" placeholder="Enter phone" value="{{ $client->phone }}">
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">Address Line 1</label>
                                                <textarea class="form-control my-2" id="address_line1" name="address_line1" placeholder="Enter address line 1">{{ $client->address_line1 }}</textarea>
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">Address Line 2</label>
                                                <textarea class="form-control my-2" id="address_line2" name="address_line2" placeholder="Enter address line 2">{{ $client->address_line2 }}</textarea>
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">Address Line 3</label>
                                                <textarea class="form-control my-2" id="address_line3" name="address_line3" placeholder="Enter address line 3">{{ $client->address_line3 }}</textarea>
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">Trading Address</label>
                                                <textarea class="form-control my-2" id="trading_address" name="trading_address" placeholder="Enter trading address">{{ $client->trading_address }}</textarea>
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">City</label>
                                                <input type="text" class="form-control my-2" id="city" name="city" placeholder="Enter city" value="{{ $client->city }}">
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">Town</label>
                                                <input type="text" class="form-control my-2" id="town" name="town" placeholder="Enter state" value="{{ $client->town }}">
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">Postal Code</label>
                                                <input type="text" class="form-control my-2" id="postcode" name="postcode" placeholder="Enter postal code" value="{{ $client->postcode }}">
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="country">Country</label>
                                                <div class="mt-2">
                                                    <select class="form-control my-2" id="country" name="country">
                                                        <option value="" disabled>Choose Country</option>
                                                        <option value="Bangladesh" {{ $client->country == 'Bangladesh' ? 'selected' : '' }}>Bangladesh</option>
                                                        <option value="UK" {{ $client->country == 'UK' ? 'selected' : '' }}>UK</option>
                                                        <option value="USA" {{ $client->country == 'USA' ? 'selected' : '' }}>USA</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="" class="mb-2">Upload Photo Id </label>
                                                <div class="position-relative">
                                                <input type="number" class="form-control" name="photo_id" id="photo_id" value="{{ $client->photo_id }}" placeholder="Upload photo id">
                                                <i class="bi bi-paperclip position-absolute top-50 translate-middle-y"
                                                    style="right: 8px;"></i>
                                                </div>
                                            </div>

                                            <div class="col-lg-4">
                                                <label for="">Reference ID</label>
                                                <input type="text" class="form-control my-2" id="reference_id" name="reference_id" placeholder="Enter reference id" value="{{ isset($client) && isset($client->refid) ? $client->refid : '' }}">
                                            </div>
                                            
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-4 mx-auto text-center">
                                                <button id="details-saveButton" class="btn btn-sm bg-theme text-light btn-outline-dark">Update</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <!-- Client details -->

                                <!-- Business info  -->
                                <div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                                    @include('admin.client.update_business')
                                </div>
                                <!-- Business info  -->

                                <!-- Director info -->
                                <div class="tab-pane fade" id="contact" role="tabpanel" aria-labelledby="contact-tab">
                                    @include('admin.client.update_director')
                                </div>
                                <!-- Director info -->

                                <!-- Service -->
                                <div class="tab-pane fade" id="service" role="tabpanel" aria-labelledby="service-tab">
                                    @include('admin.client.update_service')
                                </div>
                                <!-- Service -->

                                <!-- Contact Info -->
                                <div class="tab-pane fade" id="contact-info" role="tabpanel" aria-labelledby="contact-info-tab">
                                    @include('admin.client.update_contact')
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

<!-- Image preview start -->
<script>
    document.getElementById('pic').addEventListener('change', function(event) {
        var file = event.target.files[0];
        var reader = new FileReader();

        reader.onload = function(e) {
            document.getElementById('imagePreview').src = e.target.result;
        };

        reader.readAsDataURL(file);
    });
</script>
<!-- Image preview end -->

<!-- Client details update -->
<script>
    $(document).ready(function() {
        $('#details-saveButton').click(function(event) {
            event.preventDefault();

            var name = $('#name').val();
            var clientTypeId = $('#client_type_id').val();
            var managerId = $('#manager_id').val();

            var formData = new FormData($('#detailsForm')[0]);
            var clientId = "{{ $client->id ?? '' }}";

            formData.append('name', name);
            formData.append('client_type_id', clientTypeId);
            formData.append('manager_id', managerId);

            if(clientId) {
                $.ajax({
                    url: "/admin/client-details-update/" + clientId,
                    type: 'POST',
                    data: formData,
                    async: false,
                    success: function(response) {
                        // $('#successMessage b').text(response.message);
                        // $('#successMessage').show();
                        swal({
                            title: "Success!",
                            text: "Client details updated successfully",
                            icon: "success",
                            button: "OK",
                        });
                    },
                    error: function(xhr, status, error) {
                         var errorMessage = "";
                         if (xhr.responseJSON && xhr.responseJSON.errors){
                            $.each(xhr.responseJSON.errors, function (key, value) {
                            errorMessage += key + ": " + value.join(", ") + "<br>";
                            });
                         }else{
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

            if(clientId) {
                $.ajax({
                    url: "/admin/client-businessinfo-update/" + clientId,
                    type: 'POST',
                    data: formData,
                    async: false,
                    success: function(response) {
                        // $('#successMessage b').text(response.message);
                        // $('#successMessage').show();
                        // $('#errorMessage').hide();
                        swal({
                            title: "Success!",
                            text: "Business Info updated successfully",
                            icon: "success",
                            button: "OK",
                        });
                    },
                    error: function(xhr, status, error) {
                         var errorMessage = "";
                         if (xhr.responseJSON && xhr.responseJSON.errors){
                            $.each(xhr.responseJSON.errors, function (key, value) {
                            errorMessage += key + ": " + value.join(", ") + "<br>";
                            });
                         }else{
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
            $('#dir-phone').val(directorInfo.phone);
            $('#dir-email').val(directorInfo.email);
            $('#address').val(directorInfo.address);
            $('#dob').val(directorInfo.dob);
            $('#ni_number').val(directorInfo.ni_number);
            $('#utr_number').val(directorInfo.utr_number);
            $('#utr_authorization').val(directorInfo.utr_authorization);
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
                    swal({
                        title: "Success!",
                        text: "Director Info updated successfully",
                        icon: "success",
                        button: "OK",
                    });
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                },
                error: function(xhr, status, error) {
                    var errorMessage = "";
                    if (xhr.responseJSON && xhr.responseJSON.errors){
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
                            // $('#successMessage b').text(response.message);
                            // $('#successMessage').show();
                            // $('#errorMessage').hide();
                            swal({
                                title: "Success!",
                                text: "Director Info deleted successfully",
                                icon: "success",
                                button: "OK",
                            });
                            
                            setTimeout(function() {
                                location.reload();
                            }, 2000);  
                    },
                    error: function(xhr, status, error) {
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
            }
        });
    });
</script>
<!-- Delete director end-->

<!-- Director Info create Start -->
<script>
    $(document).ready(function () {
        $('#director-saveButton').click(function (event) {
            event.preventDefault();
            var clientId = "{{ $client->id ?? '' }}";

            var formData = new FormData($('#directorForm')[0]);
            formData.append('client_id', clientId);

            $.ajax({
                url: "{{URL::to('/admin/director-info')}}",
                type: 'POST',
                data: formData,
                async: false,
                success: function (response) {
                    if (response.status === 200) {
                        swal({
                            title: "Success!",
                            text: "Director Info created successfully",
                            icon: "success",
                            button: "OK",
                        });
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
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
            return false;
        });

        $('#director-clearButton').click(function () {
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
      var serviceId = $(this).val();
      if(serviceId) {

        var exists = false;

        $('.subServiceDetails').each(function() {
                if ($(this).find('input[name="service_id"]').val() == serviceId) {
                    exists = true;
                    return false;
                }
            });
        if (exists) {
            alert('This service is already added.');
            return;
        }

        $.ajax({
          url: '/admin/getSubServices/' + serviceId,
          type: "GET",
          dataType: "json",
          success: function(data) {
            var subServiceDetailsTemplate = `
              <div class="row mt-4 subServiceDetails">
                <div class="col-12">
                  <h5 class="p-2 bg-theme text-white mb-0 text-capitalize">Services Details</h5>
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
                          <th>Action</th>
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

            var serviceName = $('#serviceDropdown option:selected').text();

            var serviceFields = `
              <div class="row">
                <div class="col-md-12">
                  <div class="row">
                    <div class="col-md-1 text-center">
                      <h5 class="mb-3">Service</h5>
                      <p> <b>${serviceName}</b> </p>
                      <input type="hidden" name="service_id" value="${serviceId}">
                      <input type="hidden" name="client_service_id[]" value="">
                    </div>
                    <div class="col-md-2 text-center">
                        <h5 class="mb-3">Manager</h5>
                        <div class="form-check">
                            <select class="form-control mt-2 select2 managerDropdown" name="manager_id">
                            <option value="">Select</option>
                            @foreach($managers as $manager)
                            <option value="{{ $manager->id }}">{{ $manager->first_name }}</option>
                            @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2 text-center">
                        <h5 class="mb-3">Frequency</h5>
                        <div class="form-check">
                            <select class="form-control mt-2 select2 serviceFrequency" id="serviceFrequency" name="service_frequency">
                            <option value="">Select</option>
                            <option>1 Week</option>
                            <option>2 Week</option>
                            <option>4 Week</option>
                            <option>1 Month</option>
                            <option>3 Month</option>
                            <option>Annual</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2 text-center">
                        <h5 class="mb-3">Due Date</h5>
                        <div class="form-check">
                            <input type="date" class="form-control dueDate" id="dueDate" name="dueDate">
                        </div>
                    </div>
                    <div class="col-md-2 text-center">
                        <h5 class="mb-3">Target Deadline</h5>
                        <div class="form-check">
                            <input type="date" class="form-control legalDeadline" id="legalDeadline" name="legalDeadline">
                        </div>
                    </div>
                    <div class="col-md-2 text-center">
                        <h5 class="mb-3">Deadline</h5>
                        <div class="form-check">
                            <input type="date" class="form-control serviceDeadline" id="serviceDeadline" name="service_deadline">
                        </div>
                    </div>
                    <div class="col-md-1 text-center">
                      <h5 class="mb-1">Action</h5>
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
                  <td><input type="date" id="deadline" name="deadline" class="form-control"></td>
                  <td>
                    <select class="form-control select2 staffDropdown" id="selectedStaff" name="staff_id">
                      <option value="">Select Staff</option>
                      @foreach($staffs as $staff)
                      <option value="{{ $staff->id }}">{{ $staff->first_name }}</option>
                      @endforeach
                    </select>
                  </td>
                  <td><textarea name="note" id="note" rows="1" class="form-control" placeholder="Note for this task"></textarea></td>
                   <td><span class="removeSubServiceRow" style="cursor: pointer; font-size: 24px; color: red;">&#10006;</span></td>
                   <input type="hidden" class="sub-service-id" data-sub-service-id="${value.id}">
                   <input type="hidden" name="sub_service_id[]" value="${value.id}">
                   <input type="hidden" name="client_sub_service_id[]" value="">
                </tr>
              `;
              $('.subServiceDetails:last').find('tbody').append(newRow);
            });
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
            var serviceId = $('#serviceDropdown').val();
            var managerId = $('#managerDropdown').val(); 
            var service_frequency = $('#service_frequency').val(); 
            var service_deadline = $('#service_deadline').val(); 
            var subServices = [];

            $('#serviceDetailsTable tr').each(function() {
                var subServiceId = $(this).find('.sub-service-id').attr('data-sub-service-id');
                var deadline = $(this).find('input[type="date"]').val();
                var note = $(this).find('textarea').val();
                var staffId = $(this).find('.staffDropdown').val();
                
                subServices.push({
                    subServiceId: subServiceId,
                    deadline: deadline,
                    note: note,
                    staffId: staffId
                });
            });

            var data = {
                clientId: clientId,
                serviceId: serviceId,
                managerId: managerId,
                service_frequency: service_frequency,
                service_deadline: service_deadline,
                subServices: subServices
            };

            $.ajax({
                url: '/admin/store-service',
                type: 'POST',
                data: data,
                success: function(response) {
                    swal({
                        title: "Success!",
                        text: "Task assigned successfully",
                        icon: "success",
                        button: "OK",
                    });
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                },
                error: function(xhr, status, error) {
                    var errorMessage = "";
                    if (xhr.responseJSON && xhr.responseJSON.errors){
                        $.each(xhr.responseJSON.errors, function (key, value) {
                            errorMessage += key + ": " + value.join(", ") + "<br>";
                        });
                    } else {
                        errorMessage = "An error occurred. Please try again later.";
                    }
                    $('#errorMessage').html(errorMessage);
                    $('#errorMessage').show();
                    $('#successMessage').hide();
                    console.error("Error occurred: " + error);
                    console.error(xhr.responseText);
                }
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
                var service_frequency = $(this).find('#serviceFrequency').val();
                var service_deadline = $(this).find('#serviceDeadline').val();
                var due_date = $(this).find('#dueDate').val();
                var legal_deadline = $(this).find('#legalDeadline').val();
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

            $.ajax({
                url: '/admin/update-service',
                type: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json',
                success: function(response) {
                    swal({
                        title: "Success!",
                        text: "Tasks updated successfully",
                        icon: "success",
                        button: "OK",
                    });
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                },
                error: function(xhr, status, error) {
                    var errorMessage = "";
                    if (xhr.responseJSON && xhr.responseJSON.errors){
                        $.each(xhr.responseJSON.errors, function (key, value) {
                            errorMessage += key + ": " + value.join(", ") + "<br>";
                        });
                    } else {
                        errorMessage = "An error occurred. Please try again later.";
                    }
                    $('#errorMessage').html(errorMessage);
                    $('#errorMessage').show();
                    $('#successMessage').hide();
                    console.error("Error occurred: " + error);
                    console.error(xhr.responseText);
                }
            });
        });
    });
</script>
<!-- Updating services and sub services end -->

<!-- Contact Info create Start -->
<script>
    $(document).ready(function () {
        $('#contact-saveButton').click(function (event) {
            event.preventDefault();
            var clientId = "{{ $client->id ?? '' }}";

            var formData = new FormData($('#contactForm')[0]);
            formData.append('client_id', clientId);

            $.ajax({
                url: "{{URL::to('/admin/contact-info')}}",
                type: 'POST',
                data: formData,
                async: false,
                success: function (response) {
                    if (response.status === 200) {
                        // $('#successMessage b').text(response.message);
                        // $('#successMessage').show();
                        // $('#errorMessage').hide();
                        swal({
                            title: "Success!",
                            text: "Contact Info created successfully",
                            icon: "success",
                            button: "OK",
                        });
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
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
                    swal({
                        title: "Success!",
                        text: "Contact Info updated successfully",
                        icon: "success",
                        button: "OK",
                    });
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                },
                error: function(xhr, status, error) {
                    var errorMessage = "";
                    if (xhr.responseJSON && xhr.responseJSON.errors){
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
                            // $('#successMessage b').text(response.message);
                            // $('#successMessage').show();
                            // $('#errorMessage').hide();
                            swal({
                                title: "Success!",
                                text: "Contact Info deleted successfully",
                                icon: "success",
                                button: "OK",
                            });
                            setTimeout(function() {
                                location.reload();
                            }, 2000); 
                    },
                    error: function(xhr, status, error) {
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
            }
        });
    });
</script>
<!-- Delete contact end-->

<!-- Data table initialize -->
<script>
    $(document).ready(function() {
         $("#directorTable, #contactTable").DataTable({
        });
    });
</script>
<!-- Data table initialize -->

@endsection