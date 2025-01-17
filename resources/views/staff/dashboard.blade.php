@extends('staff.layouts.staff')

@section('content')

<section id="breakSection" class="section dashboard">

    <div class="row">

        <!-- Assigned service details section start -->
        <div class="col-lg-12">
            <div class="report-box border-theme sales-card p-4 mb-3 rounded-4 border-3" id="assignTaskSection" style="display: none;">
                <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                    Task Details
                </div>

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

                <div class="container-fluid">
                    <div class="row mt-3">
                        <div class="col-md-2 text-center">
                            <h5 class="mb-3">Client Name</h5>
                            <input type="text" id="client_name" class="form-control mt-2 text-center" readonly>
                        </div>    
                        <div class="col-md-2 text-center">
                            <h5 class="mb-3">Service</h5>
                            <input type="text" id="service_name" class="form-control mt-2 text-center" readonly>
                        </div>    
                        <div class="col-md-2 text-center">
                            <h5 class="mb-3">Manager</h5>
                            <input type="text" id="manager_name" class="form-control mt-2 text-center" value="" readonly>
                        </div>  
                        <div class="col-md-2 text-center">
                            <h5 class="mb-3">Frequency</h5>
                            <input type="text" id="service_frequency" class="form-control mt-2 text-center" readonly>
                        </div>   
                        <div class="col-md-2 text-center">
                            <h5 class="mb-3">Due Date</h5>
                            <!-- <input type="date" id="service_deadline" class="form-control mt-2 text-center" readonly> -->
                            <span id="due_date" class="form-control text-center" style="display: inline-block;"></span>
                        </div>
                        <div class="col-md-2 text-center">
                            <h5 class="mb-3">Deadline</h5>
                            <!-- <input type="date" id="service_deadline" class="form-control mt-2 text-center" readonly> -->
                            <span id="service_deadline" class="form-control text-center" style="display: inline-block;"></span>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Sub Service Name</th>
                                        <th>Deadline</th>
                                        <th>Staff</th>
                                        <th>Note</th>
                                        <th>Status</th>
                                        <th>Comment</th>
                                        <th>Timer</th>
                                    </tr>
                                </thead>
                                <tbody id="serviceDetailsTable"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="row mt-3 mb-3">
                        <div class="col-lg-4 mx-auto text-center">
                            <button id="sub-service-cancelButton" class="btn btn-sm btn-outline-dark">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Assigned service details section start -->

        <!-- Service message modal start -->
        <div class="modal fade" id="messageModal" tabindex="-1" role="dialog" aria-labelledby="messageModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg mt-2" role="document">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="report-box border-theme sales-card p-4 rounded-4 border-3 h-100 position-relative">
                                    <div class="card-body px-0">
                                        <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                                            Previous Comment
                                        </div>
                                        <div id="previousMessages" class="mt-4">
                                            <!-- Previous messages -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!--Message Input Section -->
                            <div class="col-md-6">
                                <div class="report-box border-theme sales-card p-4 rounded-4 border-3 h-100 position-relative">
                                    <div class="card-body px-0">
                                        <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                                        New Comment
                                        </div>
                                        <input type="hidden" id="hiddenStaffId" />
                                        <input type="hidden" id="hiddenClientSubServiceId" />
                                        <div class="form-group mt-4">
                                            <textarea class="form-control" id="service-message" rows="7" name="message" placeholder="Your comment..."></textarea>
                                        </div>
                                        <div class="text-center">
                                            <button type="button" class="mt-3 btn btn-primary bg-theme-light fs-4 border-theme border-2 fw-bold txt-theme" id="saveMessage">Send</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Service message modal end -->

        <!-- Service message modal start -->
        <div class="modal fade" id="messageModal1" tabindex="-1" role="dialog" aria-labelledby="messageModal1Label" aria-hidden="true">
            <div class="modal-dialog modal-lg mt-2" role="document">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="report-box border-theme sales-card p-4 rounded-4 border-3 h-100 position-relative">
                                    <div class="card-body px-0">
                                        <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                                            Previous Comment
                                        </div>
                                        <div id="previousMessages1" class="mt-4">
                                            <!-- Previous messages -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!--Message Input Section -->
                            <div class="col-md-6">
                                <div class="report-box border-theme sales-card p-4 rounded-4 border-3 h-100 position-relative">
                                    <div class="card-body px-0">
                                        <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                                            New Comment
                                        </div>
                                        <input type="hidden" id="hiddenClientServiceId" />
                                        <div class="form-group mt-4">
                                            <textarea class="form-control" id="service-message1" rows="7" name="message" placeholder="Your comment..."></textarea>
                                        </div>
                                        <div class="text-center">
                                            <button type="button" class="mt-3 btn btn-primary bg-theme-light fs-4 border-theme border-2 fw-bold txt-theme" id="saveMessage1">Send</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Service message modal end -->

        <div class="modal fade" id="timeModal1" tabindex="-1" aria-labelledby="timeModalLabel1" aria-hidden="true">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <div class="modal-header bg-theme-light border-theme">
                        <h5 class="modal-title txt-theme fw-bold" id="timeModal1Label">Your Time</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="report-box border-theme sales-card p-4 rounded-4 border-3">
                            <div class="card-body p-0">
                                <div class="d-flex gap-3 my-5">
                                    <div class="text-center flex-fill">
                                        <div class="fs-6 txt-theme fw-bold">Active Time</div>
                                        <div class="container">
                                            <div class="text-center fs-2 txt-theme fw-bold" id="activeTime">
                                                {{ $activeTimeFormatted ?? 'N/A' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3 align-items-center justify-content-center">
                                    <div class="col-lg-12">
                                        <a id="takeBreakBtn" class="p-2 border-theme bg-theme text-center fs-6 d-block rounded-3 border-3 text-light fw-bold" style="cursor: pointer;">Take Break</a>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-lg-12">
                                        <a href="#" onclick="checkWorkTimeStatus();" class="p-2 border-theme bg-theme text-center fs-6 d-block rounded-3 border-3 text-light fw-bold">Clock out</a>
                                        <form id="logout-form" class="d-none">
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Note modal start -->
        <div class="modal fade" id="noteModal" tabindex="-1" aria-labelledby="noteModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl mt-2">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="row">
                            <!-- Left Side Section -->
                            <div class="col-lg-4">
                                <div class="report-box border-theme sales-card p-4 rounded-4 border-3 h-100 position-relative">
                                    <div class="card-body px-0">
                                        <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                                            Your Tasks
                                        </div>
                                    
                                        <div class="mt-3">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Active:</th>
                                                        <th>Break:</th>
                                                        <th>Work Time:</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><span id="loginTime"></span></td>
                                                        <td><span id="totalBreakTime"></span></td>
                                                        <td><span id="totalDuration"></span></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <div id="completedServices">
                                            <table id="completedServicesTable" class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Client:</th>
                                                        <th>Task:</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!--  -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Side Section -->
                            <div class="col-lg-8">
                                <div class="report-box border-theme sales-card p-4 rounded-4 border-3 h-100 position-relative">
                                    <div class="card-body px-0">
                                        <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                                            Add Note
                                        </div>
                                        <form id="noteForm" method="" action="#">
                                            @csrf
                                            <div class="form-group mt-4">
                                                <label class="fw-bold mr-2">Note:</label>
                                                <textarea class="form-control" id="noteInput" rows="3" name="note" placeholder="Your notes..."></textarea>
                                            </div>

                                            <div class="form-group row mt-3 align-items-center">
                                                <div class="col">
                                                    <label class="fw-bold mr-2">Additional Work:</label>
                                                </div>
                                                <div class="col-auto">
                                                    <button type="button" class="btn btn-primary" id="addNoteRowBtn">+</button>
                                                </div>
                                            </div>

                                            <div id="additionalWorkRows">
                                                <!-- Rows -->
                                            </div>

                                            <div class="text-right mt-3">
                                                <button type="button" class="btn btn-primary bg-theme-light fs-4 border-theme border-2 fw-bold txt-theme" id="saveNoteBtn">Save Note And Log Out</button>
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
        <!-- Note modal end -->

        <div class="col-lg-4 mb-3">
            <div class="report-box border-theme sales-card p-4 rounded-4 border-3 h-100 position-relative">
                <div class="card-body px-0">
                    <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                        Your Notes
                    </div>
                    <div class="text-start my-3">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#note">
                            Add New Note
                        </button>
                    </div>
                    <div class="table-wrapper my-4 mx-auto">
                        <table id="notesTable" class="table cell-border table-striped">
                            <thead>
                                <tr>
                                    <th>Sl</th>
                                    <th>Note</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Whats happening start -->
        <div class="col-lg-8 mb-3 d-none">
            <div class="report-box border-theme sales-card p-4 rounded-4 border-3 h-100 position-relative">
                <div class="card-body px-0">
                    <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                        What's Happening at HD Accountancy
                    </div>
                    <div class="position-absolute bottom-0 mb-4" style="width:90%;">
                        <div class="d-flex align-items-center gap-3 w-full">
                            <i class="bi bi-person-circle fs-3 txt-theme"></i>
                            <input type="text" class="rounded-3 border-2 border-theme form-control" placeholder="Leave a comment">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Whats happening end -->

        <!-- assigned tasks table start-->
        <div class="col-lg-8 mb-3">
            <div class="report-box border-theme sales-card p-4 rounded-4 border-3 h-100 position-relative">
                <div class="card-body px-0">
                    <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                        Assigned Tasks
                    </div>
                <!-- Works assigned to a user and specified staff -->
                    <div class="table-wrapper my-4 mx-auto" style="width: 95%;">
                        <table id="serviceStaffTable" class="table cell-border table-striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th scope="col">Client Name</th>
                                    <th scope="col">Service Name</th>
                                    <th scope="col">Due Date</th>
                                    <th scope="col">Target Deadline</th>
                                    <th scope="col">Deadline</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                <!-- Works assigned to a user and specified staff -->
                </div>
            </div>
        </div>
        <!-- assigned tasks table end-->
    </div>

    <div class="row">

        <!-- Completed service details section start -->
        <div class="col-lg-12">
            <div class="report-box border-theme sales-card p-4 mb-3 rounded-4 border-3" id="completedTaskSection" style="display: none;">
                <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                Task Details
                </div>

                <div class="container-fluid">
                    <div class="row mt-3">
                        <div class="col-md-2 text-center">
                            <h5 class="mb-3">Client Name</h5>
                            <input type="text" id="client_name1" class="form-control mt-2 text-center" readonly>
                        </div>    
                        <div class="col-md-2 text-center">
                            <h5 class="mb-3">Service</h5>
                            <input type="text" id="service_name1" class="form-control mt-2 text-center" readonly>
                        </div>    
                        <div class="col-md-2 text-center">
                            <h5 class="mb-3">Manager</h5>
                            <input type="text" id="manager_name1" class="form-control mt-2 text-center" value="" readonly>
                        </div>  
                        <div class="col-md-2 text-center">
                            <h5 class="mb-3">Frequency</h5>
                            <input type="text" id="service_frequency1" class="form-control mt-2 text-center" readonly>
                        </div>   
                        <div class="col-md-2 text-center">
                            <h5 class="mb-3">Due Date</h5>
                            <span id="due_date1" class="form-control text-center" style="display: inline-block;"></span>
                        </div>
                        <div class="col-md-2 text-center">
                            <h5 class="mb-3">Deadline</h5>
                            <span id="service_deadline1" class="form-control text-center" style="display: inline-block;"></span>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Sub Service Name</th>
                                        <th>Deadline</th>
                                        <th>Staff</th>
                                        <th>Note</th>
                                        <th>Status</th>
                                        <th>Comment</th>
                                        <th>Timer</th>
                                    </tr>
                                </thead>
                                <tbody id="completedServiceDetailsTable"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="row mt-3 mb-3">
                        <div class="col-lg-4 mx-auto text-center">
                            <button id="completed-cancelButton" class="btn btn-sm btn-outline-dark">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Completed service details section start -->

        <!-- One Time Jobs -->
        <div class="col-lg-4 mb-3">
            <div class="report-box border-theme sales-card p-4 rounded-4 border-3 h-100 position-relative">
                <div class="card-body px-0">
                    <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                        One Time Jobs
                    </div>
                        <div class="table-wrapper my-4 mx-auto" style="width: 95%;">
                        <table id="OneTimeJobsTable" class="table cell-border table-striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th scope="col">Service Name</th>
                                    <th scope="col">Deadline</th>
                                    <th scope="col">Action</th>
                                    <th scope="col">Comment</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- One Time Jobs -->

        <!-- Completed tasks table start-->
        <div class="col-lg-8 mb-3">
            <div class="report-box border-theme sales-card p-4 rounded-4 border-3 h-100 position-relative">
                <div class="card-body px-0">
                    <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                        Completed Tasks
                    </div>
                        <div class="table-wrapper my-4 mx-auto" style="width: 95%;">
                        <table id="completedTasksTable" class="table cell-border table-striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th scope="col">Client Name</th>
                                    <th scope="col">Service Name</th>
                                    <th scope="col">Due Date</th>
                                    <th scope="col">Target Deadline</th>
                                    <th scope="col">Deadline</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- Completed tasks table end-->

    </div>

    <div class="modal fade" id="note" tabindex="-1" aria-labelledby="noteLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="noteLabel">Add Note</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <textarea class="form-control" id="note-message" rows="5" name="note-message" placeholder="Your note..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveNote">Save Note</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="actionModal" aria-labelledby="actionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="actionModalLabel">Choose an Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="note-section mb-3 p-3 border rounded bg-light d-none">
                        <h6 class="text-primary">Note Details:</h6>
                        <p id="noteDetails" class="mb-0"></p>
                    </div>
                    <button class="btn btn-success btn-one-time-job">Assign as One-Time Job</button>
                    <button class="btn btn-info btn-recent-update">Assign as Recent Update</button>

                    <div class="deadline-section mt-3 d-none">
                        <label for="deadline">Deadline:</label>
                        <input type="date" id="deadline" class="form-control">
                    </div>

                    <div class="employee-section mt-3 d-none">
                        <label for="employee_id">Assign To: <span class="text-danger">*</span></label>
                        <select id="employee_id" class="form-control" style="width: 100%;">
                            @foreach($users as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }} ({{ $employee->type }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="client-section mt-3 d-none">
                        <label for="client_id">Select Client: <span class="text-danger">*</span></label>
                        <select id="client_id" class="form-control" style="width: 100%;">
                            <option value="">Select a client</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->name }} - {{ $client->refid }} </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary btn-submit">Submit</button>
                </div>
            </div>
        </div>
    </div>

</section>


<div class="col-lg-4 mb-3" id="breakOutSection" style="display: none;">
    <div class="report-box border-theme sales-card p-4 rounded-4 border-3 h-100">
        <div class="card-body p-0">
            <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold" style="margin-bottom: 35px;">
            Clock Out to Start Work 
        </div>

        <div style="margin-bottom: 10px;"></div>
            <div class="row mt-10">
                <div class="col-lg-12">
                    <a id="breakOutBtn" class="p-2 border-theme bg-theme text-center fs-6 d-block rounded-3 border-3 text-light fw-bold" style="display: none; cursor: pointer;">Break Out</a>
                </div>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="workTimeId" value="">

@endsection

@section('script')

<script>
    var authId = @json(auth()->id());
</script>

<script>

    $('#notesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/staff/get-note',
            type: 'GET',
            dataSrc: 'data',
            error: function(xhr, error, thrown) {
                console.error(xhr.responseText);
            }
        },
        columns: [
            {
                data: null,
                render: function(data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                },
                name: 'sl',
                orderable: false,
                searchable: false
            },
            {
                data: 'content',
                name: 'content'
            },
            {
                data: 'status',
                render: function(data, type, row) {
                    if (data == 1) {
                        return '<span class="badge bg-warning text-dark">Not Assigned</span>';
                    } else if (data == 2) {
                        return '<span class="badge bg-success text-white">Assigned</span>';
                    }
                }
            },

            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false
            }
        ]
    });

    $('#saveNote').click(function () {
        var note = $('#note-message').val();
        $.ajax({
            url: '/staff/save-note',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                note: note
            },
            success: function (response) {
                toastr.success("Note saved!");

                $('#note-message').val('');
                $('#note').modal('hide');
                $('#notesTable').DataTable().ajax.reload();
            },
            error: function (xhr, status, error) {
                console.error('Error:', error);
            }
        });
    });

    $(document).on('click', '.action-btn', function () {
        const noteId = $(this).data('id');
        const noteContent = $(this).data('note');

        $('#noteDetails').text(noteContent);
        $('.note-section').removeClass('d-none');

        $('#actionModal').data('note-id', noteId);
        $('#actionModal').modal('show');

        $('.btn-submit').addClass('d-none');
    });

    $(document).on('click', '.btn-one-time-job', function () {
        $('.deadline-section').removeClass('d-none');
        $('.employee-section').removeClass('d-none');
        $('.client-section').addClass('d-none');
        $('.btn-submit').data('type', 'one-time-job');
        
        $('.btn-submit').removeClass('d-none');
    });

    $(document).on('click', '.btn-recent-update', function () {
        $('.client-section').removeClass('d-none');
        $('.deadline-section').addClass('d-none');
        $('.employee-section').addClass('d-none');
        $('.btn-submit').data('type', 'recent-update');
        
        $('.btn-submit').removeClass('d-none');

    });

    $('#actionModal').on('shown.bs.modal', function () {
        
        $('#client_id').select2({
            placeholder: "Select a client",
            allowClear: true,
            dropdownParent: $('#actionModal')
        });

        $('#employee_id').select2({
            placeholder: "Please select an employee",
            allowClear: true,
            dropdownParent: $('#actionModal')
        });
    });

    $(document).on('click', '.btn-submit', function () {
        const noteId = $('#actionModal').data('note-id');
        const noteContent = $('#noteDetails').text();
        const deadline = $('#deadline').val();
        const employeeId = $('#employee_id').val();
        const clientId = $('#client_id').val();
        const type = $(this).data('type');

        // console.log(noteId, noteContent, deadline, clientId, type);

        $.ajax({
            url: '/staff/assign-note',
            type: 'POST',
            data: {
                note_id: noteId,
                note: noteContent,
                type: type,
                deadline: deadline,
                manager_id: employeeId,
                client_id: clientId,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                toastr.success("Note assigned successfully!");

                $('#actionModal').modal('hide');
                $('#notesTable').DataTable().ajax.reload();
            },
            error: function (xhr) {
                alert('Something went wrong. Please try again.');
                console.error(xhr.responseText);
            }
        });
    });

    $('#actionModal').on('hidden.bs.modal', function () {
        $('#noteDetails').text('');
        $('#deadline').val('');
        $('#client_id').val(null).trigger('change');
        $('.deadline-section').addClass('d-none');
        $('.client-section').addClass('d-none');
        $('.btn-submit').addClass('d-none');
    });

</script>

<!-- Assigned Work List Start-->
<script>
    $(document).ready(function() {
        $('#serviceStaffTable').DataTable({
          processing: true,
          serverSide: true,
          ajax: {
              url: '/staff/get-assigned-services',
              type: 'GET',
              dataSrc: 'data',
              error: function(xhr, error, thrown) {
                    console.error('DataTables error:', error, thrown);
                }
          },
          columns: [
                { data: 'clientname', name: 'clientname' },
                { data: 'servicename', name: 'servicename' },
                { 
                    data: 'due_date', 
                    name: 'due_date'
                },
                { 
                    data: 'legal_deadline', 
                    name: 'legal_deadline'
                },
                { 
                    data: 'service_deadline', 
                    name: 'service_deadline'
                },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ]
        });

        $('#OneTimeJobsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/staff/get-one-time-jobs',
                type: 'GET',
                dataSrc: 'data',
                error: function(xhr, error, thrown) {
                    console.error('DataTables error:', error, thrown);
                }
            },
            columns: 
            [
                {
                    data: 'servicename',
                    name: 'servicename'
                },
                {
                    data: 'legal_deadline',
                    name: 'legal_deadline',
                    render: function(data, type, row) {
                        var legalDeadlineDate = moment(data, 'DD-MM-YYYY');
                        if (legalDeadlineDate.isBefore(moment(), 'day')) {
                            return '<span style="color: red;">' + data + '</span>';
                        }
                        return data;
                    }
                },
                {
                    data: 'status',
                    name: 'status',
                    render: function(data, type, row) {
                        const currentUserId = {{ Auth::id() }};

                        if (row.manager_id == currentUserId) {
                            return `
                                <select class="form-control one-time-job-status" data-id="${row.id}">
                                    <option value="1" ${data == 1 ? 'selected' : ''}>Not Started</option>
                                    <option value="0" ${data == 0 ? 'selected' : ''}>Processing</option>
                                    <option value="2" ${data == 2 ? 'selected' : ''}>Completed</option>
                                </select>
                            `;
                        }

                        const statusMap = {
                            1: 'Not Started',
                            0: 'Processing',
                            2: 'Completed'
                        };

                        return statusMap[data] ?? 'Unknown Status';
                    }
                },
                {
                    data: 'has_new_message',
                    name: 'has_new_message',
                    render: function(data, type, row) {
                        const newMessageIcon = data === 'Yes' 
                            ? '<span class="new-message-icon" style="color: red; margin-left: 5px;"><i class="fas fa-circle"></i></span>' 
                            : '';

                        return `
                            <button type="button" class="btn btn-secondary open-modal1" data-toggle="modal" data-target="#messageModal1" data-client-service-id="${row.id}">
                                <i class="fas fa-plus-circle"></i>${newMessageIcon}
                            </button>
                        `;
                    }
                }
            ]
        });

        $('#OneTimeJobsTable').on('change', '.one-time-job-status', function() {
            var newStatus = $(this).val();
            var jobId = $(this).data('id');

            $.ajax({
                url: '/staff/update-job-status/' + jobId,
                type: 'POST',
                data: {
                    status: newStatus,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.status === 200) {
                        toastr.success("Status updated successfully!");
                        if ($.fn.DataTable.isDataTable('#OneTimeJobsTable')) {
                            $('#OneTimeJobsTable').DataTable().ajax.reload(null, false);
                        }
                    } else {
                        alert('Failed to update status: ' + response.message);
                    }
                },
                error: function(xhr, error, thrown) {
                    console.error('Error updating status:', error, thrown);
                }
            });
        });

        $(document).on('click', '.open-modal1', function() {
            var clientServiceId = $(this).data('client-service-id');
            $('#hiddenClientServiceId').val(clientServiceId);
            populateMessage1(clientServiceId);
            $('#messageModal1').modal('show');
        });

        function populateMessage1(clientServiceId) {
            $.ajax({
                url: '/staff/getServiceComment/' + clientServiceId,
                type: "GET",
                dataType: "json",
                success: function(data) {
                    $('#previousMessages1').empty();
                    data.forEach(function(message) {
                        var messageDiv = $('<div>').addClass('message');
                        var userName = message.userName;
                        var messageContent = message.messageContent ? message.messageContent : '';

                        messageDiv.html('<span style="font-weight: bold;">' + userName + ': </span>' + messageContent);
                        $('#previousMessages1').append(messageDiv);
                    });
                },
                error: function(xhr, error, thrown) {
                    console.error('Error fetching previous messages:', error, thrown);
                }
            });
        }

        $('#saveMessage1').click(function() {
            var message = $('#service-message1').val();
            var clientServiceId = $('#hiddenClientServiceId').val();

            $.ajax({
                url: '/staff/store-comment',
                type: "POST",
                data: {
                    message: message,
                    client_service_id: clientServiceId,
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    $('#service-message1').val('');
                    populateMessage1(clientServiceId);
                },
                error: function(xhr, status, error) {
                    console.error('Error saving message :', error);
                }
            });
        });

        $('#messageModal1').on('hidden.bs.modal', function () {
            $('#OneTimeJobsTable').DataTable().ajax.reload();
        });

        setInterval(function () {
            $('#OneTimeJobsTable').DataTable().ajax.reload();
        }, 30000);

        $(document).on('click', '.change-status', function() {
            var clientserviceId = $(this).data('id');
            var managerFirstName = $(this).data('manager-firstname');
            var rowData = $('#serviceStaffTable').DataTable().row($(this).closest('tr')).data();
            var serviceName = rowData.servicename;
            var frequency = rowData.service_frequency;
            let deadline = rowData.service_deadline;
            let clientName = rowData.clientname;
            let dueDate = rowData.due_date;
            // deadline = deadline ? moment(deadline).format('YYYY-MM-DD') : '';

            var decodedServiceName = $('<div>').html(serviceName).text();
            $('#service_name').val(decodedServiceName);
            $('#manager_name').val(managerFirstName);
            $('#service_frequency').val(frequency);
            $('#service_deadline').text(deadline);
            $('#client_name').val(clientName);
            $('#due_date').text(dueDate);

            $.ajax({
                url: '/staff/getClientSubServices/' + clientserviceId,
                type: "GET",
                dataType: "json",
                success: function(data) {
                    populateSubServiceForm(data);
                },
                error: function(xhr, error, thrown) {
                    console.error('Error fetching sub-services:', error, thrown);
                }
            });
        });

        function populateSubServiceForm(subServices) {
            var subServiceTable = $('#serviceDetailsTable');
            subServiceTable.empty();
            var staffs = @json($staffs);
            var authUserId = {{ auth()->user()->id }};

            $.each(subServices, function(index, subService) {
                var statusText = '';
                var statusDropdown = '';
                var staff = staffs.find(function(staff) {
                    return staff.id == subService.staff_id;
                });

                var staffName = subService.staff ? (subService.staff.first_name + ' ' + (subService.staff.last_name || '')).trim() : 'N/A';
                var isAuthUserStaff = authUserId == subService.staff_id;
                var hasWorkTimes = subService.work_times && subService.work_times.length > 0;

                var hasActiveWorkTime = subService.work_times && subService.work_times.some(function(workTime) {
                    return (workTime.end_time == null || workTime.end_time == "") && workTime.is_break == 0;
                });

                if (subService.sequence_status == 0) {
                    if (isAuthUserStaff && !hasActiveWorkTime) {
                        statusDropdown = `
                            <select class="form-select change-service-status" data-sub-service-id="${subService.id}">
                                <option value="0" selected>Processing</option>
                                <option value="2">Completed</option>
                            </select>`;
                    } else {
                        statusText = 'Processing';
                    }
                } else if (subService.sequence_status == 1) {
                    statusText = 'Work isn\'t started yet';
                } else if (subService.sequence_status == 2) {
                    statusText = 'Work is completed';
                }

                var startButton = '';
                var stopButton = '';
                var duration = '';
                
                var totalDurationInSeconds = subService.work_times.filter(workTime => workTime.is_break == 0)
                    .reduce(function(acc, workTime) {
                        return acc + parseInt(workTime.duration);
                    }, 0);

                if (totalDurationInSeconds > 0) {
                    var hours = Math.floor(totalDurationInSeconds / 3600);
                    var minutes = Math.floor((totalDurationInSeconds % 3600) / 60);
                    var seconds = totalDurationInSeconds % 60;
                    duration = `<div>${hours}h ${minutes}m ${seconds}s</div>`;
                }

                if (isAuthUserStaff && subService.sequence_status == 0) {
                    if (hasActiveWorkTime) {
                        stopButton = `<button type="button" class="btn btn-danger stop-timer" data-sub-service-id="${subService.id}">Stop</button>`;
                    } else {
                        startButton = `<button type="button" class="btn btn-secondary start-timer" data-sub-service-id="${subService.id}">Start</button>`;
                    }
                }

                const newMessageIcon = subService.has_new_message
                    ? '<span class="new-message-icon" style="color: red; margin-left: 5px;"><i class="fas fa-circle"></i></span>'
                    : '';

                var newRow = `
                    <tr>
                        <td>${subService.sub_service.name}</td>
                        <td>${subService.deadline}</td>
                        <td>${staffName}</td>
                        <td>${subService.note ? subService.note : ''}</td>
                        <td>${statusText} ${statusDropdown}</td>
                        <td>
                            <button type="button" class="btn btn-secondary open-modal" data-toggle="modal" data-target="#messageModal" data-staff-id="${subService.staff_id}" data-client-sub-service-id="${subService.id}">
                                <i class="fas fa-plus-circle"></i> ${newMessageIcon}
                            </button>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                ${startButton}
                                ${stopButton}
                                <span class="badge bg-success">${duration}</span>
                            </div>
                        </td>
                    </tr>
                `;
                subServiceTable.append(newRow);
            });

            $('#assignTaskSection').show();
        }

        $(document).on('click', '.open-modal', function(){
            var staffId = $(this).data('staff-id');
            var clientSubServiceId = $(this).data('client-sub-service-id');
            $('#hiddenStaffId').val(staffId);
            $('#hiddenClientSubServiceId').val(clientSubServiceId);
            populateMessage(clientSubServiceId);
            $('#messageModal').modal('show');
        });

        function populateMessage(clientSubServiceId) {
            $.ajax({
                url: '/staff/getServiceMessage/' + clientSubServiceId,
                type: "GET",
                dataType: "json",
                success: function(data) {
                    $('#previousMessages').empty();
                    data.forEach(function(message) {
                        var messageDiv = $('<div>').addClass('message');
                        var userName = message.userName;
                        var messageContent = message.messageContent ? message.messageContent : '';

                        messageDiv.html('<span style="font-weight: bold;">' + userName + ': </span>' + messageContent);
                        $('#previousMessages').append(messageDiv);
                    });
                },
                error: function(xhr, error, thrown) {
                    console.error('Error fetching previous messages:', error, thrown);
                }
            });
        }

        $('#messageModal').on('hidden.bs.modal', function () {
            $('#assignTaskSection, #completedTaskSection').hide();
        });

        $('#saveMessage').click(function() {
            var message = $('#service-message').val();
            var staffId = $('#hiddenStaffId').val();
            var clientSubServiceId = $('#hiddenClientSubServiceId').val(); 

            $.ajax({
                url: '/staff/store-message',
                type: "POST",
                data: {
                    message: message,
                    staff_id: staffId,
                    client_sub_service_id: clientSubServiceId,
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    $('#service-message').val('');
                    populateMessage(clientSubServiceId);
                },
                error: function(xhr, status, error) {
                    console.error(error);
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

        $(document).on('change', '.change-service-status', function() {
            var clientSubServiceId = $(this).data('sub-service-id');
            var newStatus = $(this).val();

            $.ajax({
                url: '/staff/update-sub-service-status',
                type: 'POST',
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    clientSubServiceId: clientSubServiceId,
                    newStatus: newStatus
                },
                success: function(response) {
                    toastr.success(response.message);
                    
                    $('#assignTaskSection').hide();
                    $('#completedTaskSection').hide();

                    if ($.fn.DataTable.isDataTable('#serviceStaffTable')) {
                        $('#serviceStaffTable').DataTable().ajax.reload(null, false);
                    }

                    if ($.fn.DataTable.isDataTable('#completedTasksTable')) {
                        $('#completedTasksTable').DataTable().ajax.reload(null, false);
                    }

                },
                error: function(xhr, status, error) {
                    console.error(error);
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

        $('#sub-service-cancelButton').click(function() {
            $('#assignTaskSection').hide();
        });

        $(document).on('click', '.start-timer', function() {
            var clientSubServiceId = $(this).data('sub-service-id');
            $.ajax({
                    type: 'POST',
                    url: '/staff/start-work-time',
                    data: {
                        clientSubServiceId: clientSubServiceId,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        
                        toastr.success('Time has been started successfully!');

                        $('#assignTaskSection').hide();
                        $('#completedTaskSection').hide();

                        if ($.fn.DataTable.isDataTable('#serviceStaffTable')) {
                            $('#serviceStaffTable').DataTable().ajax.reload(null, false);
                        }

                        if ($.fn.DataTable.isDataTable('#completedTasksTable')) {
                            $('#completedTasksTable').DataTable().ajax.reload(null, false);
                        }
                    },
                    error: function(xhr, status, error) {
                       console.error(xhr.responseText);
                    }
                });
        });

        $(document).on('click', '.stop-timer', function() {
            var clientSubServiceId = $(this).data('sub-service-id');
            $.ajax({
                type: 'POST',
                url: '/staff/stop-work-time',
                data: {
                    clientSubServiceId: clientSubServiceId,
                    _token: "{{ csrf_token() }}" 
                },
                success: function(response) {
                    toastr.success('Time has been stopped successfully!');

                    $('#assignTaskSection').hide();
                    $('#completedTaskSection').hide();

                    if ($.fn.DataTable.isDataTable('#serviceStaffTable')) {
                        $('#serviceStaffTable').DataTable().ajax.reload(null, false);
                    }

                    if ($.fn.DataTable.isDataTable('#completedTasksTable')) {
                        $('#completedTasksTable').DataTable().ajax.reload(null, false);
                    }
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                }
            });
        });

        $(document).on('click', '.start-break', function() {
            var clientSubServiceId = $(this).data('sub-service-id');
            $.ajax({
                type: 'POST',
                url: '/staff/start-break',
                data: {
                    clientSubServiceId: clientSubServiceId,
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {

                    toastr.success('Break has been started successfully!');

                    $('#assignTaskSection').hide();
                    $('#completedTaskSection').hide();

                    if ($.fn.DataTable.isDataTable('#serviceStaffTable')) {
                        $('#serviceStaffTable').DataTable().ajax.reload(null, false);
                    }

                    if ($.fn.DataTable.isDataTable('#completedTasksTable')) {
                        $('#completedTasksTable').DataTable().ajax.reload(null, false);
                    }
                },
                error: function(xhr, status, error) {
                     console.error(xhr.responseText);
                }
            });
        });

        $(document).on('click', '.stop-break', function() {
            var clientSubServiceId = $(this).data('sub-service-id');
            var workTimesId = $(this).data('work-times-id');
            $.ajax({
                type: 'POST',
                url: '/staff/stop-break',
                data: {
                    clientSubServiceId: clientSubServiceId,
                     workTimesId: workTimesId,
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {

                    toastr.success('Time has been stopped successfully!');

                    $('#assignTaskSection').hide();
                    $('#completedTaskSection').hide();

                    if ($.fn.DataTable.isDataTable('#serviceStaffTable')) {
                        $('#serviceStaffTable').DataTable().ajax.reload(null, false);
                    }

                    if ($.fn.DataTable.isDataTable('#completedTasksTable')) {
                        $('#completedTasksTable').DataTable().ajax.reload(null, false);
                    }
                },
                error: function(xhr, status, error) {
                   console.error(xhr.responseText);
                }
            });
        }); 
  });
</script>
<!-- Assigned Work List End-->

<!-- Task Check before loggin out start -->
<script>
    function checkWorkTimeStatus() {
        fetchClientSubServices();
        $.ajax({
            url: '/staff/check-work-time-status', 
            type: 'GET',
            success: function(response) {
                if (response.status === 'ongoing') {
                    toastr.warning("You have an ongoing task. Please complete it before logging out!", "Warning");
                } else {
                    $('#noteModal').modal('show');
                }
            },
            error: function(xhr, status, error) {
                console.error(error);
            }
        });
    }
</script>
<!-- Task Check before loggin out end -->

<!-- Completed Work List -->
<script>
    $(document).ready(function() {
        $('#completedTasksTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/staff/get-completed-services',
                type: 'GET',
                dataSrc: 'data',
                error: function(xhr, error, thrown) {
                    console.error('DataTables error:', error, thrown);
                }
            },
            columns: [
                { data: 'clientname', name: 'clientname' },
                { data: 'servicename', name: 'servicename' },
                { 
                    data: 'due_date', 
                    name: 'due_date'
                },
                { 
                    data: 'legal_deadline', 
                    name: 'legal_deadline'
                },
                { 
                    data: 'service_deadline', 
                    name: 'service_deadline'
                },
                { 
                    data: 'action', 
                    name: 'action',
                    orderable: false, 
                    searchable: false 
                }
            ]
        });

        $(document).on('click', '.task-details', function() {
            var clientserviceId = $(this).data('id');
            var managerFirstName = $(this).data('manager-firstname');
            var rowData = $('#completedTasksTable').DataTable().row($(this).closest('tr')).data();
            var serviceName = rowData.servicename;
            var frequency = rowData.service_frequency;
            let deadline = rowData.service_deadline;
            let clientName = rowData.clientname;
            let dueDate = rowData.due_date;
            // deadline = deadline ? moment(deadline).format('YYYY-MM-DD') : '';

            // $('#service_name1').val(serviceName);
            var decodedServiceName = $('<div>').html(serviceName).text();
            $('#service_name1').val(decodedServiceName);
            $('#manager_name1').val(managerFirstName);
            $('#service_frequency1').val(frequency);
            $('#service_deadline1').text(deadline);
            $('#client_name1').val(clientName);
            $('#due_date1').text(dueDate);

            $.ajax({
                url: '/staff/getClientSubServices/' + clientserviceId,
                type: "GET",
                dataType: "json",
                success: function(data) {
                    populateCompletedForm(data);
                },
                error: function(xhr, error, thrown) {
                    console.error('Error fetching sub-services:', error, thrown);
                }
            });
        });

        function populateCompletedForm(subServices) {
            var completedServiceDetailsTable = $('#completedServiceDetailsTable');
            completedServiceDetailsTable.empty();

            var staffs = @json($staffs);

            $.each(subServices, function(index, subService) {
                var staff = staffs.find(function(staff) {
                    return staff.id == subService.staff_id;
                });

                var statusDropdown = '';
                var authUserId = {{ auth()->user()->id }};
                var isAuthUserStaff = authUserId == subService.staff_id;

                var staffName = subService.staff ? (subService.staff.first_name + ' ' + (subService.staff.last_name || '')).trim() : 'N/A';

                var totalDurationInSeconds = subService.work_times.filter(workTime => workTime.is_break == 0)
                    .reduce(function(acc, workTime) {
                        return acc + parseInt(workTime.duration);
                    }, 0);

                var duration = '';

                if (totalDurationInSeconds > 0) {
                    var hours = Math.floor(totalDurationInSeconds / 3600);
                    var minutes = Math.floor((totalDurationInSeconds % 3600) / 60);
                    var seconds = totalDurationInSeconds % 60;
                    duration = `<div>${hours}h ${minutes}m ${seconds}s</div>`;
                }

                var statusDropdown = `
                    <select class="form-select change-service" data-sub-service-id="${subService.id}" ${isAuthUserStaff && subService.sequence_status == 2 ? '' : 'disabled'}>
                        <option value="0" ${subService.sequence_status == 0 ? 'selected' : ''}>Processing</option>
                        <option value="2" ${subService.sequence_status == 2 ? 'selected' : ''}>Work is completed</option>
                    </select>`;

                const newMessageIcon = subService.has_new_message
                ? '<span class="new-message-icon" style="color: red; margin-left: 5px;"><i class="fas fa-circle"></i></span>'
                : '';

                var newRow = `
                    <tr>
                        <td>${subService.sub_service.name}</td>
                        <td>${subService.deadline}</td>
                        <td>${staffName}</td>
                         <td>${subService.note ? subService.note : ''}</td>
                        <td>${statusDropdown}</td>
                        <td>
                            <button type="button" class="btn btn-secondary open-modal" data-toggle="modal" data-target="#messageModal" data-staff-id="${subService.staff_id}" data-client-sub-service-id="${subService.id}">
                                <i class="fas fa-plus-circle"></i> ${newMessageIcon}
                            </button>
                        </td>
                         <td>
                            <span class="badge bg-success">${duration}</span>
                        </td>
                    </tr>
                `;
                completedServiceDetailsTable.append(newRow);
            });

            $('#completedTaskSection').show();
        }

        $(document).on('change', '.change-service', function() {
            var clientSubServiceId = $(this).data('sub-service-id');
            var newStatus = $(this).val();
            $.ajax({
                url: '/staff/change-sub-service-status',
                type: 'POST',
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    clientSubServiceId: clientSubServiceId,
                    newStatus: newStatus
                },
                success: function(response) {
                    toastr.success('Status changed successfully!');

                    $('#assignTaskSection').hide();
                    $('#completedTaskSection').hide();

                    if ($.fn.DataTable.isDataTable('#serviceStaffTable')) {
                        $('#serviceStaffTable').DataTable().ajax.reload(null, false);
                    }

                    if ($.fn.DataTable.isDataTable('#completedTasksTable')) {
                        $('#completedTasksTable').DataTable().ajax.reload(null, false);
                    }
                },
                error: function(xhr, status, error) {
                    console.error(error);
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

        $('#completed-cancelButton').click(function() {
            $('#completedTaskSection').hide();
        });
    });
</script>
<!-- Completed Work List -->

<!-- Take Break Start -->
<script>
    $(document).ready(function() {
        checkBreakStatus($('#workTimeId').val());

        $('#takeBreakBtn').click(function(event) {
            event.preventDefault();

            $.ajax({
                url: '/staff/take-break',
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                data: {},
                success: function(response) {
                    $('#workTimeId').val(response.workTimeId);
                    localStorage.setItem('workTimeId', response.workTimeId);
                    checkBreakStatus(response.workTimeId);
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                }
            });
        });

        $('#breakOutBtn').click(function(event) {
            event.preventDefault();

            var workTimeId = localStorage.getItem('workTimeId');
            if (workTimeId) {
                $.ajax({
                    url: '/staff/break-out',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    data: { workTimeId: workTimeId },
                    success: function(response) {
                        localStorage.setItem('isBreak', false);
                        $('#breakOutSection').hide();
                        $('#breakSection').show();
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', xhr.responseText);
                    }
                });
            }
        });
    });

    function checkBreakStatus(workTimeId) {
        if (workTimeId) {
            $.ajax({
                url: '/staff/check-break-status',
                type: 'GET',
                data: { workTimeId: workTimeId },
                success: function(response) {
                    if (response.isBreak) {
                        localStorage.setItem('isBreak', true);
                        $('#breakSection').hide();
                        $('#breakOutSection').show();
                    } else {
                        localStorage.setItem('isBreak', false);
                        $('#breakSection').show();
                        $('#breakOutSection').hide();
                    }
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                }
            });
        }
    }

    $(window).on('load', function() {
        var isBreak = localStorage.getItem('isBreak');
        if (isBreak === 'true') {
            $('#breakSection').hide();
            $('#breakOutSection').show();
        } else {
            $('#breakSection').show();
            $('#breakOutSection').hide();
        }
    });
</script>
<!-- Take Break End -->

<!-- Data showing in modal start  -->
<script>
    function fetchClientSubServices() {
        var csrfToken = "{{ csrf_token() }}";

        $.ajax({
            url: '/staff/get-completed-services-modal',
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            success: function(response) {

                function formatDuration(seconds) {
                    var hours = Math.floor(seconds / 3600);
                    var minutes = Math.floor((seconds % 3600) / 60);
                    var remainingSeconds = seconds % 60;
                    
                    return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
                }

                var duration = moment.duration(response.login_time, 'seconds');
                var formattedLoginTime = moment.utc(duration.asMilliseconds()).format('HH:mm:ss');

                $('#loginTime').text(formattedLoginTime);
                $('#totalBreakTime').text(formatDuration(response.total_break_duration));
                $('#totalDuration').text(formatDuration(response.total_duration));

                var completedServicesHtml = '';
                $.each(response.completed_services, function(index, item) {
                    completedServicesHtml += '<tr>';
                    completedServicesHtml += '<td>' + item.client_name + '</td>';
                    completedServicesHtml += '<td>' + item.sub_service_name + '</td>';
                    completedServicesHtml += '</tr>';
                });
                $('#completedServicesTable tbody').html(completedServicesHtml);
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
            }
        });
    }
</script>
<!-- Data showing in modal end  -->

<!-- Note and additional work start -->
<script>
    $(document).ready(function() {
        $('#addNoteRowBtn').click(function() {
            var newRowHtml = `
                <div class="mt-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex flex-column">
                            <label>Client:</label>
                            <select class="form-control px-3 py-2 client-name" style="width: 115px;">
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}">{{ $client->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex flex-column">
                            <label>Sub Service:</label>
                            <select class="form-control px-3 py-2 sub-service-name" style="width: 115px;">
                                @foreach($subServices as $subService)
                                    <option value="{{ $subService->id }}">{{ $subService->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex flex-column">
                            <label>Note:</label>
                            <textarea class="form-control px-3 py-2 note" rows="1" style="width: 115px;" id="subServiceNote" placeholder="Note"></textarea>
                        </div>
                        <div class="d-flex flex-column">
                            <label>Start Time:</label>
                            <input type="time" class="form-control px-3 py-2 start-time" style="width: 130px;">
                        </div>
                        <div class="d-flex flex-column">
                            <label>End Time:</label>
                            <input type="time" class="form-control px-3 py-2 end-time" style="width: 130px;">
                        </div>
                        <button type="button" class="btn btn-danger btn-remove-note-row">-</button>
                    </div>
                </div>`;
                
            $('#additionalWorkRows').append(newRowHtml);
        });

        $(document).on('click', '.btn-remove-note-row', function() {
            $(this).closest('.mt-3').remove();
        });

        $('#saveNoteBtn').click(function(e) {
            e.preventDefault();

            var formData = {
                _token: '{{ csrf_token() }}',
                client_ids: [],
                sub_service_ids: [],
                notes: [],
                noteInput: '', 
                start_times: [],
                end_times: []
            };

            $('#additionalWorkRows .client-name').each(function() {
                formData.client_ids.push($(this).val());
            });

            $('#additionalWorkRows .sub-service-name').each(function() {
                formData.sub_service_ids.push($(this).val());
            });

            $('#additionalWorkRows .note').each(function() {
                formData.notes.push($(this).val());
            });

            $('#additionalWorkRows .start-time').each(function() {
                formData.start_times.push($(this).val());
            });

            $('#additionalWorkRows .end-time').each(function() {
                formData.end_times.push($(this).val());
            });

            var noteValue = $('#noteInput').val();
            formData.noteInput = noteValue;

            $.ajax({
                url: '/staff/save-notes',
                type: 'POST',
                data: formData,
                success: function(response) {
                
                    toastr.success("Record saved and you will be logged out now!", "Success");

                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                }
            });
        });
    });
</script>
<!-- Note and additional work end -->

<!-- Idle time count start -->
<!-- <script>
    let idleTimer;
    let idleStartTime;
    let totalIdleTime = 0;

    function startIdleTimer() {
        idleStartTime = Date.now();
        idleTimer = setTimeout(checkIdleStatus, 10 * 1000); 
    }

    function checkIdleStatus() {
        const idleDuration = Date.now() - idleStartTime;
        totalIdleTime += idleDuration;

        logIdleTime(totalIdleTime);
        startIdleTimer();
    }

    function resetIdleTimer() {
        clearTimeout(idleTimer);
        idleStartTime = Date.now();
        totalIdleTime = 0;
        startIdleTimer();
    }

    function logIdleTime(idleTime) {
        $.ajax({
            url: '{{ route('staff.idle.time') }}',
            type: 'POST',
            data: {
                idle_time: idleTime
            },
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                // console.log('Idle time logged successfully.');
            },
            error: function(xhr, status, error) {
                console.error('Error logging idle time:', error);
            }
        });
    }

    document.addEventListener('mousemove', resetIdleTimer);
    document.addEventListener('keypress', resetIdleTimer);

    startIdleTimer();
</script> -->
<!-- Idle time count end -->

<!-- Active Time start -->
<script>
    function updateActiveTime() {
        var activeTimeElement = document.getElementById('activeTime');
        var currentTime = activeTimeElement.textContent;
        
        var timeArray = currentTime.split(':');
        var hours = parseInt(timeArray[0], 10);
        var minutes = parseInt(timeArray[1], 10);
        var seconds = parseInt(timeArray[2], 10);

        seconds++;
        if (seconds >= 60) {
            seconds = 0;
            minutes++;
        }
        if (minutes >= 60) {
            minutes = 0;
            hours++;
        }
        var formattedTime = ('0' + hours).slice(-2) + ':' + ('0' + minutes).slice(-2) + ':' + ('0' + seconds).slice(-2);
        activeTimeElement.textContent = formattedTime;
    }

    setInterval(updateActiveTime, 1000);
</script>
<!-- Active Time end -->

@endsection