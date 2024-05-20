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
                    <li class="nav-item flex-fill" role="presentation">
                        <button class="nav-link w-100 text-capitalize" id="holiday-tab" data-bs-toggle="tab"
                        data-bs-target="#holiday-tab-content" type="button" role="tab" aria-controls="holiday-tab-content"
                        aria-selected="false">Holiday Types</button>
                    </li>
                </ul>

                <div class="tab-content pt-2" id="myTabjustifiedContent">

                    <!-- Job  Tab start -->
                    <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
                        <form action="">
                            <div class="row my-4">
                                <div class="col-lg-6">
                                    <div class="px-2">
                                        <h2 class="txt-theme" id="fullname"></h2>
                                        <h4 class="txt-theme" id="departmentname"></h4>
                                        <h5 class="txt-theme" id="phoneNumber"></h5>
                                        <h6 class="txt-theme fw-bold" id="emailNumber"></h6>
                                    </div>
                                </div>
                                <div class="col-lg-6 text-end">
                                    <div style="margin-right: 10px; margin-bottom: 10px; padding-right: 10px;">
                                        <img width="160" src="" class="border-theme border-2 rounded-3 user_image" id="user_image">
                                    </div>
                                    <div class="col-12">
                                        <div class="row px-3">
                                            <div class="col-lg-4 d-flex align-items-center justify-content-end">
                                                <small>sort by:</small>
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
                                        <span id="completedTaskCount">count:</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-12 d-flex" id="completedTasksContainer">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Service Name</th>
                                            <th>Created By</th>
                                            <th>Assign Date</th>
                                            <th>Due Date</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>


                            <div class="sub-box-header txt-theme">
                                <div class="row w-100">
                                    <div class="col-lg-6 d-flex">
                                        <span>Work In Progress</span>
                                    </div>
                                    <div class="col-lg-6 text-end">
                                        <span id="inProgressTaskCount">count:</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-12 d-flex" id="inProgressTasksContainer">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Service Name</th>
                                            <th>Created By</th>
                                            <th>Assign Date</th>
                                            <th>Due Date</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>


                            <div class="sub-box-header txt-theme">
                                <div class="row w-100">
                                    <div class="col-lg-6 d-flex">
                                        <span>Due Tasks</span>
                                    </div>
                                    <div class="col-lg-6 text-end">
                                        <span id="dueTaskCount">count:</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-12 d-flex" id="dueTasksContainer">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Service Name</th>
                                            <th>Created By</th>
                                            <th>Assign Date</th>
                                            <th>Due Date</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
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
                                            <img width="160" src="" class="border-theme border-2 rounded-3 user_image" id="imagePreview">              
                                        </div>
                                        <label for="pic" class="mb-0" style="cursor: pointer;">
                                            <i class="bi bi-cloud-upload"></i>
                                            <big>Update Image</big>
                                        </label>
                                        <input type="file" name="" id="pic" class="invisible">
                                    </div>
                                </div>
                                <input type="hidden" id="hiddenStaffId" name="hiddenStaffId">

                                <!-- Personal Information Start -->
                                <div class="sub-box-header">
                                    <div class="row w-100">
                                        <div class="col-lg-6 d-flex txt-theme">
                                            <span>Personal Information</span>
                                        </div>
                                        <div class="col-lg-6 text-end">
                                            <a href="#" class="txt-theme edit-personal-info" onclick="toggleEdit()"><i class="bi bi-pencil-square"></i> Edit</a>
                                            <a href="#" class="txt-theme update-personal-info" style="display: none;"><i class="bi bi-check-square"></i> Update</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="row px-3 my-2 txt-theme">
                                    <div class="col-lg-6">
                                        <table class="w-100 text-capitalize">
                                            <tr>
                                                <td><b>First Name:</b></td>
                                                <td><input type="text" id="first_name" readonly></td>
                                            </tr>
                                            <tr>
                                                <td><b>Last Name:</b></td>
                                                <td><input type="text" id="last_name" readonly></td>
                                            </tr>
                                            <tr>
                                                <td><b>Phone:</b></td>
                                                <td><input type="number" id="phone" readonly></td>
                                            </tr>
                                            <tr>
                                                <td><b>Email:</b></td>
                                                <td><input type="email" id="email" readonly></td>
                                            </tr>
                                            <tr>
                                                <td><b>NI Number:</b></td>
                                                <td><input type="text" id="ni_number" readonly></td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-lg-6">
                                        <table class="w-100 txt-theme text-capitalize">
                                            <tr>
                                                <td><b>Date Of Birth:</b></td>
                                                <td><input type="date" id="date_of_birth" readonly></td>
                                            </tr>
                                            <tr>
                                                <td><b>Address 1:</b></td>
                                                <td><input type="text" id="address_1" readonly></td>
                                            </tr>
                                            <tr>
                                                <td><b>Address 2:</b></td>
                                                <td><input type="text" id="address_2" readonly></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                                <!-- Personal Information End -->


                                 <!-- Job Information start -->
                                <div class="sub-box-header">
                                    <div class="row w-100">
                                        <div class="col-lg-6 d-flex txt-theme">
                                            <span>Job Information</span>
                                        </div>
                                        <div class="col-lg-6 text-end">
                                            <a href="#" class="txt-theme edit-job-info" onclick="toggleEditJobInfo()"><i class="bi bi-pencil-square"></i> Edit</a>
                                             <a href="#" class="txt-theme update-job-info" style="display: none;"><i class="bi bi-check-square"></i> Update</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="row px-3 my-2 txt-theme">
                                    <div class="col-lg-6">
                                        <table class="w-100 text-capitalize">
                                            <tr>
                                                <td><b>Department:</b></td>
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
                                                <td><b>Job title:</b></td>
                                                <td><input type="text" id="job_title" readonly></td>
                                            </tr>
                                            <tr>
                                                <td><b>Employment Status:</b></td>
                                                <td><input type="text" id="employment_status" readonly></td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-lg-6">
                                        <table class="w-100 txt-theme text-capitalize">
                                            <tr>
                                                <td><b>Reporting ID:</b></td>
                                                <td><input type="number" id="reporting_id" readonly></td>
                                            </tr>
                                            <tr>
                                                <td><b>Joining Date:</b></td>
                                                <td><input type="date" id="joining_date" readonly></td>
                                            </tr>
                                            <tr>
                                                <td><b>Reporting To:</b></td>
                                                <td>
                                                    <select id="reporting_to" readonly>
                                                        <option value="">Select Manager</option>
                                                        @foreach($managers as $manager)
                                                             <option value="{{ $manager->id }}" data-id-number="{{ $manager->id_number }}">{{ $manager->first_name }}</option>
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

                    <!-- Holiday Tab start -->
                    <div class="tab-pane fade" id="holiday-tab-content" role="tabpanel" aria-labelledby="holiday-tab">
                        <table class="table" id="holidayTable">
                            <thead>
                                <tr>
                                    <th>Holiday Type</th>
                                    <th>Day</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Content will be dynamically added here by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                    <!-- Holiday Tab end -->
                </div>
                <!-- End Default Tabs -->
            </div>
          </div>
        </div>
    </div>
</section>

<!-- <style>
    input[type="text"],
    input[type="number"],
    input[type="email"],
    input[type="date"],
    select {
        border: 0.3px solid #ccc;
        background-color: rgba(255, 255, 255, 0.5);
        width: 100%; 
        padding: 5px; 
        margin: 0;
        box-shadow: none; 
        outline: none;
    }
</style> -->

<style>
    input[type="text"],
    input[type="number"],
    input[type="email"],
    input[type="date"],
    select {
        border: none;
        background-color: transparent; 
        width: 100%; 
        padding: 0; 
        margin: 0; 
        box-shadow: none;
        outline: none; 
    }
    
</style>


@endsection

@section('script')

<script>
    $(document).ready(function() {

        function populateCompletedTasks(tasks) {
            var container = $('#completedTasksContainer').find('tbody');
            container.empty();
            if (tasks.length === 0) {
                container.append('<tr><td colspan="4">No completed tasks found</td></tr>');
                $('#completedTaskCount').text('count: 0');
            } else {
                $('#completedTaskCount').text('count: ' + tasks.length);
                tasks.forEach(function(task, index) {
                    var assignDate = moment(task.created_at).format('DD.MM.YYYY');
                    var dueDate = moment(task.deadline).format('DD.MM.YYYY');
                    var taskRow = `
                        <tr>
                            <td>${task.sub_service.name}</td>
                            <td>${task.created_by.first_name}</td>
                            <td>${assignDate}</td>
                            <td>${dueDate}</td>
                        </tr>
                    `;
                    container.append(taskRow);
                });
            }
        }

        function populateInProgressTasks(tasks) {
            var container = $('#inProgressTasksContainer').find('tbody');
            container.empty();
            if (tasks.length === 0) {
                container.append('<tr><td colspan="4">No tasks in progress found</td></tr>');
                $('#inProgressTaskCount').text('count: 0');
            } else {
                $('#inProgressTaskCount').text('count: ' + tasks.length);
                tasks.forEach(function(task, index) {
                    var assignDate = moment(task.created_at).format('DD.MM.YYYY');
                    var dueDate = moment(task.deadline).format('DD.MM.YYYY');
                    var taskRow = `
                        <tr>
                            <td>${task.sub_service.name}</td>
                            <td>${task.created_by.first_name}</td>
                            <td>${assignDate}</td>
                            <td>${dueDate}</td>
                        </tr>
                    `;
                    container.append(taskRow);
                });
            }
        }

        function populateDueTasks(tasks) {
            var container = $('#dueTasksContainer').find('tbody');
            container.empty();
            if (tasks.length === 0) {
                container.append('<tr><td colspan="4">No due tasks found</td></tr>');
                $('#dueTaskCount').text('count: 0');
            } else {
                $('#dueTaskCount').text('count: ' + tasks.length);
                tasks.forEach(function(task, index) {
                    var assignDate = moment(task.created_at).format('DD.MM.YYYY');
                    var dueDate = moment(task.deadline).format('DD.MM.YYYY');
                    var taskRow = `
                        <tr>
                            <td>${task.sub_service.name}</td>
                            <td>${task.created_by.first_name}</td>
                            <td>${assignDate}</td>
                            <td>${dueDate}</td>
                        </tr>
                    `;
                    container.append(taskRow);
                });
            }
        }

        function fetchCompletedTasks(staffId) {
            $.ajax({
                url: "/admin/completed-tasks",
                method: "GET",
                data: { staff_id: staffId },
                success: function(response) {
                    // console.log("Completed Tasks:", response);
                    populateCompletedTasks(response.completedTasks);
                },
                error: function(xhr, status, error) {
                     console.error(xhr.responseText);
                }
            });
        }

        function fetchInProgressTasks(staffId) {
            $.ajax({
                url: "/admin/tasks-in-progress",
                method: "GET",
                data: { staff_id: staffId },
                success: function(response) {
                    // console.log("Tasks in Progress:", response);
                    populateInProgressTasks(response.inProgressTasks);
                },
                error: function(xhr, status, error) {
                     console.error(xhr.responseText);
                }
            });
        }

        function fetchCanceledTasks(staffId) {
            $.ajax({
                url: "/admin/due-tasks",
                method: "GET",
                data: { staff_id: staffId },
                success: function(response) {
                    // console.log("Canceled Tasks:", response);
                     populateDueTasks(response.dueTasks);
                },
                error: function(xhr, status, error) {
                     console.error(xhr.responseText);
                }
            });
        }

        function fetchAndDisplayHolidays(staffId) {
            $.ajax({
                url: '/admin/holidays', 
                method: 'GET',
                data: { staff_id: staffId },
                success: function(response) {
                    var tableBody = $('#holidayTable tbody');
                    tableBody.empty();

                    response.forEach(function(holiday) {
                        var row = `
                            <tr>
                                <td>${holiday.holiday_type_name}</td>
                                <td>${holiday.day}</td>
                                <td>
                                    <button class="edit-day-btn btn btn-sm bg-theme text-light btn-outline-dark" data-holiday-id="${holiday.holiday_type_id}" data-staff-holiday-type-id="${holiday.staff_holiday_type_id}">Edit Day</button>
                                </td>
                            </tr>
                        `;
                        tableBody.append(row);
                    });
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                }
            });
        }

        $(document).on('click', '.edit-day-btn', function() {
             var staffHolidayTypeId = $(this).data('staff-holiday-type-id');
            //  console.log(staffHolidayTypeId);
             var staffId = $('#hiddenStaffId').val();
             var holidayTypeId = $(this).data('holiday-id');
             var newDay = prompt('Enter the new day:');
             if (newDay) {
                var token = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url: '/admin/holidays/update',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token
                    },
                    data: {
                        staffHolidayTypeId: staffHolidayTypeId,
                        day: newDay,
                        staff_id: staffId,
                        holiday_type_id: holidayTypeId  
                    },
                    success: function(response) {
                        swal({
                        title: "Success!",
                        text: "Day updated successfully",
                        icon: "success",
                        button: "OK",
                    });
                        fetchAndDisplayHolidays(staffId);
                    },
                    error: function(xhr, status, error) {
                         console.error(xhr.responseText);
                    }
                });
            }
        });

        $('#staffsTable').on('click', '.edit-staff', function() {
            var staffId = $(this).data('staff-id');
            fetchCompletedTasks(staffId);
            fetchInProgressTasks(staffId);
            fetchCanceledTasks(staffId);
            fetchAndDisplayHolidays(staffId);
        });

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

    function toggleEdit(staffId) {
        var editButton = document.querySelector('.edit-personal-info');
        var updateButton = document.querySelector('.update-personal-info');

        if (editButton.innerHTML.includes('Edit')) {
            $('input[type="text"], input[type="number"], input[type="email"], input[type="date"]').prop('readonly', false);
            editButton.innerHTML = '<i class="bi bi-x-square"></i> Cancel';
            updateButton.style.display = 'inline-block';
        } else {
            $('input[type="text"], input[type="number"], input[type="email"], input[type="date"]').prop('readonly', true);
            editButton.innerHTML = '<i class="bi bi-pencil-square"></i> Edit';
            updateButton.style.display = 'none';
        }
    }

    function toggleEditJobInfo(staffId) {
        var editButton = document.querySelector('.edit-job-info');
        var updateButton = document.querySelector('.update-job-info');

        if (editButton.innerHTML.includes('Edit')) {
            $('select, input[type="text"], input[type="number"], input[type="date"]').prop('readonly', false);
            editButton.innerHTML = '<i class="bi bi-x-square"></i> Cancel';
            updateButton.style.display = 'inline-block';
        } else {
            $('select, input[type="text"], input[type="number"], input[type="date"]').prop('readonly', true);
            editButton.innerHTML = '<i class="bi bi-pencil-square"></i> Edit';
            updateButton.style.display = 'none';
        }
    }

    $(document).on('click', '.edit-staff', function(e) {
        e.preventDefault();
        var staffId = $(this).data('staff-id');
        $('#hiddenStaffId').val(staffId);
        toggleEdit(staffId);
        toggleEditJobInfo(staffId);
        
        $.ajax({
            url: '/admin/get-staff-details/' + staffId,
            method: 'GET',
            success: function(response) {
                // console.log(response);
                $('#first_name').val(response.first_name);
                $('#last_name').val(response.last_name);
                var fullName = response.first_name + ' ' + response.last_name;
                $('#fullname').text(fullName);
                var departmentName = response.department ? response.department.name : '';
                $('#departmentname').text(departmentName);
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
                $('#reporting_id').val(response.reporting_user ? response.reporting_user.id_number : '');
                $('#joining_date').val(response.joining_date); 
                $('#reporting_to').val(response.reporting_user ? response.reporting_user.first_name : '');


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

                var imageUrl = "{{ asset('images/staff') }}/";
                if (response.image) {
                    imageUrl += response.image;
                } else {
                    imageUrl = "{{ asset('assets/img/human-placeholder.jpg') }}";
                }
                $('.user_image').attr('src', imageUrl);

            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
            }
        });
    });

    $(document).on('click', '.update-personal-info', function(e) {
        e.preventDefault();
        sendUpdateRequest();
    });

    $(document).on('click', '.update-job-info', function(e) {
        e.preventDefault();
        sendUpdateRequestJobInfo();
    });

    function sendUpdateRequest() {
        var staffId = $('#hiddenStaffId').val();
        // console.log(staffId);
        var formData = new FormData();
        formData.append('staff_id', staffId);
        formData.append('first_name', $('#first_name').val());
        formData.append('last_name', $('#last_name').val());
        formData.append('phone', $('#phone').val());
        formData.append('email', $('#email').val());
        formData.append('ni_number', $('#ni_number').val());
        formData.append('date_of_birth', $('#date_of_birth').val());
        formData.append('address_1', $('#address_1').val());
        formData.append('address_2', $('#address_2').val());

        var imageFile = $('#pic')[0].files[0];
        if (imageFile) {
            formData.append('image', imageFile);
        }

        $.ajax({
            url: '/admin/staff-personal-update', 
            type: 'POST',
            data: formData, 
            processData: false,
            contentType: false, 
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                swal({
                    title: "Success!",
                    text: "Staff personal information updated successfully",
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

    $('#reporting_to').change(function() {
        var selectedManagerId = $(this).val();
        var selectedManagerIdNumber = $('#reporting_to option:selected').data('id-number');
        $('#reporting_id').val(selectedManagerIdNumber);
    });

    function sendUpdateRequestJobInfo() {
        var staffId = $('#hiddenStaffId').val();
        var data = {
            staff_id: staffId,
            department: $('#department').val(),
            job_title: $('#job_title').val(),
            employment_status: $('#employment_status').val(),
            reporting_id: $('#reporting_id').val(),
            joining_date: $('#joining_date').val(),
            reporting_to: $('#reporting_to').val(),
        };

        $.ajax({
            url: '/admin/staff-job-update', 
            type: 'POST',
            data: data, 
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                swal({
                    title: "Success!",
                    text: "Staff job details updated successfully",
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