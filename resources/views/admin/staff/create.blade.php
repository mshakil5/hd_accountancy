@extends('admin.layouts.admin')

@section('content')

<section class="section">
    <div class="row">
        <div class="col-lg-12 px-0 border shadow-sm">
            <p class="p-2 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center">
                <i class='bx bxs-user-plus fs-4 me-2'></i> New Staff Entry
            </p>

            <div class="row px-3">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="tab-content pt-2" id="myTabjustifiedContent">

                                <!-- Staff Form -->
                                <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">

                                    <form id="myForm">
                                        <div class="row my-4">
                                            <div class="col-lg-9">
                                            </div>
                                            <div class="col-lg-3 text-center">
                                                <div class="img mb-2">
                                                    <img src="{{ asset('assets/img/human-placeholder.jpg') }} " id="imagePreview" width="150" class="border-theme border-2 rounded-3">
                                                </div>
                                                <label for="pic" class="mb-0" style="cursor: pointer;">
                                                    <i class="bi bi-cloud-upload"></i>
                                                    <small>Upload Image</small>
                                                </label>
                                                <input type="file" id="pic" name="image" class="invisible">
                                            </div>

                                            <div class="col-lg-4">
                                                <label for="">First Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control my-2" id="first_name" name="first_name" placeholder="Enter first name">
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">Last Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control my-2" id="last_name" name="last_name" placeholder="Enter last name">
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">Phone <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control my-2" id="phone" name="phone" placeholder="Enter phone">
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">Email <span class="text-danger">*</span></label>
                                                <input type="email" class="form-control my-2" id="email" name="email" placeholder="Enter email">
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">NI Number <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control my-2" id="ni_number" name="ni_number" placeholder="Enter NI number">
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">Date of Birth <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control my-2" id="date_of_birth" name="date_of_birth" placeholder="Enter date of birth">
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">Address line 1 <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control my-2" id="address_line1" name="address_line1" placeholder="Enter address line 1">
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">Address line 2</label>
                                                <input type="text" class="form-control my-2" id="address_line2" name="address_line2" placeholder="Enter address line 2">
                                            </div>
                                            <!-- <div class="col-lg-4">
                                                <label for="">Address line 3</label>
                                                <input type="text" class="form-control my-2" id="address_line3" name="address_line3" placeholder="Enter address line 3">
                                            </div> -->
                                            <div class="col-lg-4">
                                                <label for="">Town <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control my-2" id="town" name="town" placeholder="Enter town">
                                            </div>
                                                                                                                                                                            <div class="col-lg-4">
                                                <label for="">Post Code <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control my-2" id="postcode" name="postcode" placeholder="Enter post code">
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="country">Department <span class="text-danger">*</span></label>
                                                <div class="mt-2">
                                                <select class="form-control my-2" id="department_id" name="department_id">
                                                    <option value="" selected disabled>Choose Department</option>
                                                    @foreach($departments as $department)
                                                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                                                    @endforeach
                                                </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">Job Title <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control my-2" id="job_title" name="job_title" placeholder="Enter job title">
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">Employment Status</label>
                                                <input type="text" class="form-control my-2" id="employment_status" name="employment_status" placeholder="Enter employment status">
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">Joining Date Status</label>
                                                <input type="date" class="form-control my-2" id="joining_date" name="joining_date" placeholder="Enter joining date">
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">Reporting To <span class="text-danger">*</span></label>
                                                  <div class="mt-2">
                                                    <select class="form-control my-2" name="reporting_to" id="reporting_to">
                                                        <option value="">Please select</option>
                                                        @foreach($managers as $manager)
                                                        <option value="{{ $manager->id }}" data-id-number="{{ $manager->id_number }}">{{ $manager->first_name }} {{ $manager->last_name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">Reporting Employee ID <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control my-2" id="reporting_employee_id" name="reporting_employee_id" placeholder="Reporting Employee ID" readonly>
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">Password</label>
                                                <div class="input-group my-2">
                                                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter password">
                                                    <button type="button" class="btn btn-outline-secondary toggle-password" data-target="#password">
                                                        <i class="fa fa-eye"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="col-lg-4">
                                                <label for="">Confirm Password</label>
                                                <div class="input-group my-2">
                                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Enter confirm password">
                                                    <button type="button" class="btn btn-outline-secondary toggle-password" data-target="#confirm_password">
                                                        <i class="fa fa-eye"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="col-lg-4">
                                                <div class="form-group">
                                                    <label>Role <span class="text-danger">*</span></label>
                                                    <select class="form-control" id="role_id" name="role_id">
                                                        <option value="">Select role</option>
                                                        @foreach ($roles as $role)
                                                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-4 mx-auto text-center">
                                                <button id="clearButton" class="btn btn-sm btn-outline-dark">Clear</button>
                                                <button id="saveButton" class="btn btn-sm bg-theme text-light btn-outline-dark">Save</button>
                                            </div>
                                        </div>
                                    </form>

                                </div>
                                <!-- Staff Form-->
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

</script>

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

<!-- Staff Start -->
<script>
    $(document).ready(function () {
        $('#reporting_to').change(function() {
            var managerId = $(this).val();
            var managerIdNumber = $(this).find('option:selected').data('id-number');
            $('#reporting_employee_id').val(managerIdNumber);
        });

        $('#saveButton').click(function (event) {
            event.preventDefault();
            var saveButton = $(this);
            saveButton.prop('disabled', true);
            var formData = new FormData($('#myForm')[0]);

            $.ajax({
                url: "{{URL::to('/admin/staff')}}",
                type: 'POST',
                data: formData,
                async: false,
                success: function (response) {
                        swal({
                            title: "Success!",
                            text: "Staff created successfully",
                            icon: "success",
                            button: "OK",
                        });
                    saveButton.prop('disabled', false);
                    setTimeout(function() {
                        window.location.href = "{{ route('allStaff') }}";
                    }, 2000);
                },
                error: function (xhr, status, error) {
                    var errorMessage = "An error occurred. Please try again later.";
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        errorMessage = Object.values(xhr.responseJSON.errors)[0][0];
                    }
                        saveButton.prop('disabled', false);

                        swal({
                            title: "Error",
                            text: errorMessage,
                            icon: "error",
                            button: "OK",
                        });
                },
                cache: false,
                contentType: false,
                processData: false
            });
            return false;
        });

        $('#clearButton').click(function () {
            event.preventDefault();
            $('#myForm')[0].reset();
        });
    });
</script>
<!-- Staff End -->

@endsection