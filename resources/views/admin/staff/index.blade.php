@extends('admin.layouts.admin')

@section('content')

<!-- Main content -->
<section class="section dashboard">
    <div class="row">
        <div class="col-lg-12 px-0 shadow-sm border-theme border-2">
            <p class="p-2 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center">
                <i class="bx bxs-user-plus fs-4 me-2"></i> All Staffs
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
                {{-- <div class="col-lg-4 p-3">
                    <input type="search" name="" placeholder="Search..." class="form-control" id="">
                </div>
                <div class="col-lg-4 p-3">
                    <input type="search" name="" placeholder="Sort By" class="form-control" id="">
                </div> --}}
                <div class="col-lg-12 p-3 text-end">
                    <a href="{{ route('createStaff') }}" class="btn btn-sm bg-theme text-light btn-outline-dark">+ New Staff</a>
                </div>
            </div>
            <p class="sub-box-header" class="">
                <i class='bx bxs-user-plus fs-4 me-2'></i>
                <span>staff details</span>
            </p>
            <div class="row">
                <div class="col-lg-6">
                <div class="table-wrapper my-4 mx-auto" style="width: 95%;">
                    <table class="table cell-border table-bordered table-striped" id="staffsTable">
                        <thead>
                            <tr>
                                <th scope="col">Sl</th>
                                <th scope="col">Staff ID</th>
                                <th scope="col">Name</th>
                                <!-- <th scope="col">Phone</th> -->
                                <!-- <th scope="col">Email</th> -->
                                <!-- <th scope="col">Job Title</th> -->
                                <th scope="col">Status</th> 
                                <th scope="col">Action</th> 
                            </tr>
                        </thead>
                        <tbody>
                            <!-- DataTables -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-lg-6 ps-0" style="border-left:1px solid #233969;">

                <!-- Default Tabs -->
                <ul class="border  nav nav-tabs d-flex border-theme" id="myTabjustified" role="tablist">
                    <li class="nav-item flex-fill" role="presentation">
                        <button class="nav-link w-100 text-capitalize active" id="home-tab" data-bs-toggle="tab"
                        data-bs-target="#home" type="button" role="tab" aria-controls="home" aria-selected="true">job
                        profile</button>
                    </li>
                    <li class="nav-item flex-fill" role="presentation">
                        <button class="nav-link w-100 text-capitalize" id="profile-tab" data-bs-toggle="tab"
                        data-bs-target="#profile" type="button" role="tab" aria-controls="profile"
                        aria-selected="false">other Information</button>
                    </li>
                </ul>

                <div class="tab-content pt-2" id="myTabjustifiedContent">

                    <!-- Job  Tab start -->
                    <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
                        <form action="">
                            <div class="row my-4">
                            <div class="col-lg-6 ">
                                <div class="px-2">
                                <h2 class="txt-theme" id="fullname"></h2>
                                    <h4 class="txt-theme" id="departmentname"></h4>
                                    <h5 class="txt-theme" id="phoneNumber"></h5>
                                        <h6 class="txt-theme fw-bold" id="emailNumber"></h6>
                                </div>
                            </div>
                            <div class="col-lg-6 text-end">
                                <div>
                                <!-- <img width="160" src="https://picsum.photos/seed/picsum/200/200"
                                    class="border-theme border-2 rounded-3 me-2 mb-3"> -->
                                    <img width="160" src="" class="border-theme border-2 rounded-3" id="user_image">
                                </div>
                                <div class="col-12">
                                <div class="row px-3">
                                    <div class="col-lg-4 d-flex align-items-center justify-content-end">
                                    <small>sort by: </small>
                                    </div>
                                    <div class="col-lg-8">
                                    <select name="" id="" class="form-control">
                                        <option value="">last 7 days</option>
                                    </select>
                                    </div>
                                </div>

                                </div>
                            </div>

                            </div>
                            <div class="sub-box-header txt-theme">
                            <div class="row w-100">
                                <div class="col-lg-6 d-flex">
                                <span>Task completed</span>
                                </div>
                                <div class="col-lg-6 text-end">
                                count:3
                                </div>
                            </div>
                            </div>      
                            <div class="row offset-1 my-2 txt-theme">
                            <div class="col-lg-3">job 1</div>
                            <div class="col-lg-3">assign by</div>
                            <div class="col-lg-3">assign date</div>
                            <div class="col-lg-3">due date</div>
                            </div>      
                            <div class="row offset-1 my-2 txt-theme">
                            <div class="col-lg-3">job 2</div>
                            <div class="col-lg-3">assign by</div>
                            <div class="col-lg-3">assign date</div>
                            <div class="col-lg-3">due date</div>
                            </div>      
                            <div class="row offset-1 my-2 txt-theme">
                            <div class="col-lg-3">job 3</div>
                            <div class="col-lg-3">assign by</div>
                            <div class="col-lg-3">assign date</div>
                            <div class="col-lg-3">due date</div>
                            </div>
                            <div class="sub-box-header txt-theme">
                            <div class="row w-100">
                                <div class="col-lg-6 d-flex">
                                <span>Work in Process</span>
                                </div>
                                <div class="col-lg-6 text-end">
                                count:2
                                </div>
                            </div>
                            </div>      
                            <div class="row offset-1 my-2 txt-theme">
                            <div class="col-lg-3">job 1</div>
                            <div class="col-lg-3">assign by</div>
                            <div class="col-lg-3">assign date</div>
                            <div class="col-lg-3">due date</div>
                            </div>      
                            <div class="row offset-1 my-2 txt-theme">
                            <div class="col-lg-3">job 2</div>
                            <div class="col-lg-3">assign by</div>
                            <div class="col-lg-3">assign date</div>
                            <div class="col-lg-3">due date</div>
                            </div>      
                            <div class="sub-box-header txt-theme">
                            <div class="row w-100">
                                <div class="col-lg-6 d-flex">
                                <span>Due task</span>
                                </div>
                                <div class="col-lg-6 text-end">
                                count:1
                                </div>
                            </div>
                            </div>      
                            <div class="row offset-1 my-2 txt-theme">
                            <div class="col-lg-3">job 1</div>
                            <div class="col-lg-3">assign by</div>
                            <div class="col-lg-3">assign date</div>
                            <div class="col-lg-3">due date</div>
                            </div>
                        </form>
                    </div>
                    <!-- Job  Tab end -->

                    <!-- profile Tab start -->
                    <div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                        <div class="row my-4">

                            <form action="">
                                <div class="row my-4">
                                    <div class="col-lg-6"></div>
                                    <div class="col-lg-6 text-center">
                                        <div class="img mb-2">
                                            <img width="160" src="" class="border-theme border-2 rounded-3" id="user_image">
                                        </div>
                                        <i class="bi bi-cloud-upload"></i>
                                        <label for="pic">
                                            <big class="txt-theme">Upload Logo / Photo</big>
                                        </label>
                                        <input type="file" name="" id="pic" class="invisible">
                                    </div>
                                </div>

                                <!-- Personal Information  Start -->
                                <div class="sub-box-header">
                                    <div class="row w-100">
                                        <div class="col-lg-6 d-flex txt-theme">
                                            <span>Personal Information</span>
                                        </div>
                                        <div class="col-lg-6 text-end">
                                            <a href="#" class="txt-theme edit-personal-info" onclick="toggleEdit()"><i class="bi bi-pencil-square"></i> Edit</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="row px-3 my-2 txt-theme">
                                    <div class="col-lg-6">
                                        <table class="w-100 text-capitalize">
                                            <tr>
                                                <td><b>First name</b></td>
                                                <td><input type="text" id="first_name" readonly></td>
                                            </tr>
                                            <tr>
                                                <td><b>Last name</b></td>
                                                <td><input type="text" id="last_name" readonly></td>
                                            </tr>
                                            <tr>
                                                <td><b>Phone</b></td>
                                                <td><input type="number" id="phone" readonly></td>
                                            </tr>
                                            <tr>
                                                <td><b>Email</b></td>
                                                <td><input type="email" id="email" readonly></td>
                                            </tr>
                                            <tr>
                                                <td><b>NI number</b></td>
                                                <td><input type="text" id="ni_number" readonly></td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-lg-6">
                                        <table class="w-100 txt-theme text-capitalize">
                                            <tr>
                                                <td><b>Date of birth</b></td>
                                                <td><input type="date" id="date_of_birth" readonly></td>
                                            </tr>
                                            <tr>
                                                <td><b>Address 1</b></td>
                                                <td><input type="text" id="address_1" readonly></td>
                                            </tr>
                                            <tr>
                                                <td><b>Address 2</b></td>
                                                <td><input type="text" id="address_2" readonly></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                                 <!-- Personal Information  End -->

                                <!-- Job Information start -->
                                <div class="sub-box-header">
                                    <div class="row w-100">
                                        <div class="col-lg-6 d-flex txt-theme">
                                            <span>Job Information</span>
                                        </div>
                                        <div class="col-lg-6 text-end">
                                            <a href="#" class="txt-theme edit-job-info" onclick="toggleEditJobInfo()"><i class="bi bi-pencil-square"></i> Edit</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="row px-3 my-2 txt-theme">
                                    <div class="col-lg-6">
                                        <table class="w-100 text-capitalize">
                                            <tr>
                                                <td><b>Department</b></td>
                                                <td>
                                                    <select id="department" name="department" class="department-select" readonly>
                                                        <option value="">Select Department</option>
                                                        @foreach($departments as $department)
                                                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td><b>Job title</b></td>
                                                <td><input type="text" id="job_title" readonly></td>
                                            </tr>
                                            <tr>
                                                <td><b>Employment Status</b></td>
                                                <td><input type="text" id="employment_status" readonly></td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-lg-6">
                                        <table class="w-100 txt-theme text-capitalize">
                                            <tr>
                                                <td><b>Reporting ID</b></td>
                                                <td><input type="number" id="reporting_id" readonly></td>
                                            </tr>
                                            <tr>
                                                <td><b>Joining Date</b></td>
                                                <td><input type="date" id="joining_date" readonly></td>
                                            </tr>
                                            <tr>
                                                <td><b>Reporting to</b></td>
                                                <td>
                                                    <select id="reporting_to" readonly>
                                                        <option value="">Select Manager</option>
                                                        @foreach($managers as $manager)
                                                            <option value="{{ $manager->id }}">{{ $manager->first_name }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                                <!-- Job Information end -->
                            </form>

                        </div>
                    </div>
                    <!-- profile Tab end -->

                </div>
                <!-- End Default Tabs -->
            </div>
          </div>
        </div>
    </div>
</section>
@endsection

@section('script')

<script>
    $(document).ready(function() {
        var table = $('#staffsTable').DataTable({
            serverSide: true,
            ajax: "{{ route('get.Stuffs') }}",
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex'},
                {data: 'id_number', name: 'id_number'},
                {
                    data: null,
                    name: 'name',
                    render: function(data, type, full, meta) {
                        var fullName = '';
                        if (full.first_name && full.last_name) {
                            fullName = full.first_name + ' ' + full.last_name;
                        } else if (full.first_name) {
                            fullName = full.first_name;
                        } else if (full.last_name) {
                            fullName = full.last_name;
                        }
                        return fullName;
                    }
                },
                {
                    data: 'status',
                    name: 'status',
                    render: function(data, type, full, meta) {
                        var statusClass = data ? 'btn btn-secondary' : 'btn btn-danger';
                        var statusIcon = data ? '<i class="fas fa-toggle-on"></i>' : '<i class="fas fa-toggle-off"></i>';
                        return '<button class="' + statusClass + '" onclick="changeStatus(' + full.id + ')">' + statusIcon + '</button>';
                    }
                },
                {
                    data: 'id',
                    name: 'details',
                    render: function(data, type, full, meta) {
                        var editButtonHtml = '<button class="btn btn-secondary edit-staff" data-staff-id="' + data + '"><i class="fa fa-eye"></i></button>';
                        var deleteButtonHtml = '<button class="btn btn-danger delete-staff" data-staff-id="' + data + '" style="margin-left: 10px;"><i class="fas fa-trash"></i></button>';

                        return editButtonHtml + deleteButtonHtml;
                    }
                }
            ]
        });
    });

    $(document).on('click', '.edit-staff', function(e) {
        e.preventDefault();
        var staffId = $(this).data('staff-id');
        toggleEdit(staffId);
        
        $.ajax({
            url: '/admin/get-staff-details/' + staffId,
            method: 'GET',
            success: function(response) {
                console.log(response);
                $('#first_name').val(response.first_name);
                $('#last_name').val(response.last_name);
                var fullName = response.first_name + ' ' + response.last_name;
                $('#fullname').text(fullName);
                $('#departmentname').text(response.department.name);
                $('#phoneNumber').text(response.phone);
                $('#emailNumber').text(response.email);
                $('#phone').val(response.phone);
                $('#email').val(response.email);
                $('#ni_number').val(response.ni_number);
                $('#date_of_birth').val(response.date_of_birth);
                $('#address_1').val(response.address_line1);
                $('#address_2').val(response.address_line2);
                $('#job_title').val(response.job_title);
                $('#employment_status').val(response.employment_status); 
                $('#reporting_id').val(response.reporting_user.id_number);
                $('#joining_date').val(response.joining_date); 
                $('#reporting_to').val(response.reporting_user.first_name); 

                var departmentId = response.department.id; 

                var departmentOption = $('#department option[value="' + departmentId + '"]');
                if (departmentOption.length > 0) {
                    departmentOption.prop('selected', true);
                }

                var managerId = response.reporting_user.id; 

                var managerOption = $('#reporting_to option[value="' + managerId + '"]');
                if (managerOption.length > 0) {
                    managerOption.prop('selected', true);
                }

                var imageUrl = "{{ asset('images/staff') }}/" + response.image;
                $('#user_image').attr('src', imageUrl); 
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
            }
        });
    });

    function toggleEdit(staffId) {
        var inputs = document.querySelectorAll('input[type="text"], input[type="number"], input[type="email"], input[type="date"]');
        var editButton = document.querySelector('.edit-personal-info');

        if (inputs[0].readOnly) {
            inputs.forEach(function(input) {
                input.readOnly = false;
            });
            editButton.innerHTML = '<i class="bi bi-pencil-square"></i> Update';
            editButton.onclick = function() {
                sendUpdateRequest(staffId);
            };
        } else {
            inputs.forEach(function(input) {
                input.readOnly = true;
            });
            editButton.innerHTML = '<i class="bi bi-pencil-square"></i> Edit';
            editButton.onclick = function() {
                toggleEdit(staffId);
            };
        }
    }

    function sendUpdateRequest(staffId) {
        var data = {
            staff_id: staffId,
            first_name: document.getElementById('first_name').value,
            last_name: document.getElementById('last_name').value,
            phone: document.getElementById('phone').value,
            email: document.getElementById('email').value,
            ni_number: document.getElementById('ni_number').value,
            date_of_birth: document.getElementById('date_of_birth').value,
            address_1: document.getElementById('address_1').value,
            address_2: document.getElementById('address_2').value
        };

        console.log(data);

        $.ajax({
            url: '/admin/staff-personal-update', 
            type: 'POST',
            data: data,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                swal({
                    title: "Success!",
                    text: "Task updated successfully",
                    icon: "success",
                    button: "OK",
                });
                setTimeout(function() {
                    location.reload();
                }, 2000);
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
            }
        });
    }
</script>



<!-- Job Information start -->
<script>
    function toggleEditJobInfo() {
        var inputs = document.querySelectorAll('input[type="text"], input[type="number"], input[type="date"]');
        var editButton = document.querySelector('.edit-job-info');
        var departmentSelect = document.querySelector('select.department-select');

        if (inputs[0].readOnly) {
            inputs.forEach(function(input) {
                input.readOnly = false;
            });
            departmentSelect.disabled = false;
            editButton.innerHTML = '<i class="bi bi-pencil-square"></i> Update';
            editButton.onclick = function() {
                var staffId = $('.edit-staff').data('staff-id');
                sendUpdateRequestJobInfo(staffId);
            };
        } else {
            inputs.forEach(function(input) {
                input.readOnly = true;
            });
            departmentSelect.disabled = true;
            editButton.innerHTML = '<i class="bi bi-pencil-square"></i> Edit';
            editButton.onclick = toggleEditJobInfo;
        }
    }

    function sendUpdateRequestJobInfo(staffId) {
        var data = {
            staff_id: staffId,
            department: document.getElementById('department').value,
            job_title: document.getElementById('job_title').value,
            employment_status: document.getElementById('employment_status').value,
            reporting_id: document.getElementById('reporting_id').value,
            joining_date: document.getElementById('joining_date').value,
        };

        console.log(data);

        // Ajax
    }
</script>
<!-- Job Information end -->



{{-- Delete staff start --}}
<script>
    $(document).ready(function() {
        $(document).on('click', '.delete-staff', function(e) {
            e.preventDefault();
            var staffId = $(this).data('staff-id');

            if (confirm("Are you sure you want to delete this staff member?")) {
                $.ajax({
                    url: '/admin/delete-staff/' + staffId, 
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') 
                    },
                    success: function(response) {
                        if (response.status === 200) {
                            swal({
                                title: "Success!",
                                text: "Staff deleted successfully",
                                icon: "success",
                                button: "OK",
                            });
                            $('#staffsTable').DataTable().ajax.reload();
                        } else {
                                Toastify({
                                    text: "Failed to delete."
                                }).showToast();
                            }
                    },
                    error: function(xhr, status, error) {
                    
                    }
                });
            }
        });
    });
</script>
{{-- Delete staff end --}}

<!-- Staff status change start -->
<script>
    function changeStatus(userId) {
        $.ajax({
            url: "{{ route('staff.change.status') }}",
            method: "POST",
            data: {
                user_id: userId,
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                if (response.success) {
                    $('#staffsTable').DataTable().ajax.reload();
                    Toastify({
                        text: "Status changed successfully!"
                    }).showToast();
                } else {
                    Toastify({
                        text: "Failed to change status."
                    }).showToast();
                }
            }
        });
    }
</script>
<!-- Staff status change end -->

@endsection