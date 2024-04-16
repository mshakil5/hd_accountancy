@extends('admin.layouts.admin')

@section('content')

<section class="section">
    <div class="row">
        <div class="col-lg-12 px-0 border shadow-sm">
            <p class="p-2 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center">
                <i class='bx bxs-user-plus fs-4 me-2'></i> New Staff Entry
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

            <div class="row px-3">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="tab-content pt-2" id="myTabjustifiedContent">

                                <!-- Staff Form -->
                                <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">

                                    <form id="myForm">
                                        <div class="row my-4">
                                            <div class="col-lg-4">
                                                <label for="">First Name</label>
                                                <input type="text" class="form-control my-2" id="first_name" name="first_name" placeholder="Enter first name">
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">Last Name</label>
                                                <input type="text" class="form-control my-2" id="last_name" name="last_name" placeholder="Enter last name">
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">Phone</label>
                                                <input type="number" class="form-control my-2" id="phone" name="phone" placeholder="Enter phone">
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">Email</label>
                                                <input type="email" class="form-control my-2" id="email" name="email" placeholder="Enter email">
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">NI Number</label>
                                                <input type="number" class="form-control my-2" id="ni_number" name="ni_number" placeholder="Enter NI number">
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">Date of Birth</label>
                                                <input type="date" class="form-control my-2" id="date_of_birth" name="date_of_birth" placeholder="Enter date of birth">
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">Address</label>
                                                <input type="text" class="form-control my-2" id="address" name="address" placeholder="Enter address">
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="country">Department</label>
                                                <div class="mt-2">
                                                <select class="form-control select2 my-2" id="department_id" name="department_id">
                                                    <option value="" selected disabled>Choose Department</option>
                                                    @foreach($departments as $department)
                                                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                                                    @endforeach
                                                </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">Job Title</label>
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
                                                <label for="">Upload Image</label>
                                                <input type="file" class="form-control my-2" id="image" name="image" required>
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">Reporting To</label>
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
                                                <label for="">Reporting Employee ID</label>
                                                <input type="number" class="form-control my-2" id="reporting_employee_id" name="reporting_employee_id" placeholder="Reporting Employee ID" readonly>
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">Password</label>
                                                <input type="password" class="form-control my-2" id="password" name="password" placeholder="Enter password">
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="">Confirm Password</label>
                                                <input type="password" class="form-control my-2" id="confirm_password" name="confirm_password" placeholder="Enter confirm password">
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
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
</script>

<!-- Staff Start -->
<script>
    $(document).ready(function () {

        //Change the reporting id automatically
        $('#reporting_to').change(function() {
            var managerId = $(this).val();
            var managerIdNumber = $(this).find('option:selected').data('id-number');
            $('#reporting_employee_id').val(managerIdNumber);
        });

        $('#saveButton').click(function (event) {
            event.preventDefault();

            var formData = new FormData($('#myForm')[0]);

            $.ajax({
                url: "{{URL::to('/admin/staff')}}",
                type: 'POST',
                data: formData,
                async: false,
                success: function (response) {
                    if (response.status === 200) {
                        $('#successMessage b').text(response.message);
                        $('#successMessage').show();
                        $('#errorMessage').hide();
                        $('#myForm')[0].reset();
                    } else {
                        $('#errorMessage b').text(response.message);
                        $('#errorMessage').show();
                        $('#successMessage').hide();
                        $('#myForm')[0].reset();
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

        $('#clearButton').click(function () {
            event.preventDefault();
            $('#myForm')[0].reset();
        });
    });
</script>
<!-- Staff End -->

@endsection