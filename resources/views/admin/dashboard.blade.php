@extends('admin.layouts.admin')

@section('content')

@if (in_array('1', json_decode(Auth::user()->role->permission)))

<section class="section dashboard">
    <div class="row">

        <!-- Left side columns -->
        <div class="col-lg-12">
            <div class="row">

                <!-- Top 4 start -->
                <div class="col-lg-3">
                    <div class="report-box py-1 sales-card">
                        <div class="card-body d-flex flex-column align-items-center">
                            <div class="card-icon  bg-transparent d-flex align-items-center justify-content-center">
                                <i class="bi bi-headphones fs-1 txt-theme"></i>
                            </div>

                            <h5 class="card-title text-capitalize py-1 mb-3">This month holiday</h5>
                            <h3 class="fw-bold txt-theme mb-0">4</h3>

                        </div>

                    </div>
                </div>

                <div class="col-lg-3">
                    <div class="report-box py-1 sales-card">
                        <div class="card-body d-flex flex-column align-items-center">
                            <div class="card-icon  bg-transparent d-flex align-items-center justify-content-center">
                                <svg xmlns="http://www.w3.org/2000/svg" height="40" fill="#093B63"
                                    class="bi bi-person-wheelchair  txt-theme" viewBox="0 0 16 16">
                                    <path
                                        d="M12 3a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3m-.663 2.146a1.5 1.5 0 0 0-.47-2.115l-2.5-1.508a1.5 1.5 0 0 0-1.676.086l-2.329 1.75a.866.866 0 0 0 1.051 1.375L7.361 3.37l.922.71-2.038 2.445A4.73 4.73 0 0 0 2.628 7.67l1.064 1.065a3.25 3.25 0 0 1 4.574 4.574l1.064 1.063a4.73 4.73 0 0 0 1.09-3.998l1.043-.292-.187 2.991a.872.872 0 1 0 1.741.098l.206-4.121A1 1 0 0 0 12.224 8h-2.79zM3.023 9.48a3.25 3.25 0 0 0 4.496 4.496l1.077 1.077a4.75 4.75 0 0 1-6.65-6.65z" />
                                </svg>
                            </div>

                            <h5 class="card-title text-capitalize py-1 mb-3">today's sick</h5>
                            <h3 class="fw-bold txt-theme mb-0">0</h3>

                        </div>

                    </div>
                </div>

                <div class="col-lg-3">
                    <div class="report-box py-1 sales-card">
                        <div class="card-body d-flex flex-column align-items-center">
                            <div class="card-icon  bg-transparent d-flex align-items-center justify-content-center">
                                <i class="bi bi-calendar-x txt-theme"></i>
                            </div>

                            <h5 class="card-title text-capitalize py-1 mb-3">Today's absent</h5>
                            <h3 class="fw-bold txt-theme mb-0">{{$totalAbsentStaffCount}}</h3>

                        </div>

                    </div>
                </div>

                <div class="col-lg-3">
                    <div class="report-box py-1 sales-card">
                        <div class="card-body d-flex flex-column align-items-center">
                            <div class="card-icon  bg-transparent d-flex align-items-center justify-content-center">
                                <i class="bi bi-clock txt-theme"></i>
                            </div>

                            <h5 class="card-title text-capitalize py-1 mb-3">today's total hour</h5>
                            <h3 class="fw-bold txt-theme mb-0">0</h3>

                        </div>

                    </div>
                </div>
                <!-- Top 4 end -->

                <!-- Task to be assigned details start -->
                <div class="col-lg-12">
                    <div class="report-box border-theme sales-card p-4 mb-3 rounded-4 border-3" id="assignTaskSection" style="display: none;">
                        <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                            Assign Task
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

                        <div class="col-md-12">
                            <div class="row mt-3">
                                <div class="col-2 text-center">
                                    <h5 class="mb-3">Choose Service</h5>
                                    <div class="form-check">
                                        <input type="hidden" id="clientId">
                                        <select id="servicesDropdown" class="form-control mt-2" disabled>
                                            <option value="">Select Service</option>
                                            @foreach($services as $service)
                                            <option value="{{ $service->id }}">
                                                {{ $service->name }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-2 text-center">
                                    <h5 class="mb-3">Choose Manager</h5>
                                    <div class="form-check">
                                        <select id="managerDropdown" class="form-control mt-2">
                                            <option value="" selected>Select Manager</option>
                                            @foreach($managers as $manager)
                                            <option value="{{ $manager->id }}">
                                                {{ $manager->first_name }} {{ $manager->last_name }} ({{ $manager->type }})
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-2 text-center">
                                    <h5 class="mb-3">Choose Frequency</h5>
                                    <div class="form-check">
                                        <select id="service_frequency" class="form-control mt-2" name="service_frequency">
                                            <option value="" selected>Select Frequency</option>
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
                                    <h5 class="mb-3">Due Date</h5>
                                    <div class="form-check">
                                        <input type="text" class="form-control dueDate" id="dueDate" name="dueDate">
                                    </div>
                                </div>
                                <div class="col-md-2 text-center">
                                    <h5 class="mb-3">Target Deadline</h5>
                                    <div class="form-check">
                                        <input type="text" class="form-control legalDeadline" id="legalDeadline" name="legalDeadline">
                                    </div>
                                </div>
                                <div class="col-2 text-center">
                                    <h5 class="mb-3">Deadline</h5>
                                    <div class="form-check">
                                        <input type="text" id="service_deadline" class="form-control mt-2 serviceDeadline" name="service_deadline">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <table class="table mt-3">
                            <thead>
                                <tr>
                                    <th>Sub Service Name</th>
                                    <th>Deadline</th>
                                    <th>Staff</th>
                                    <th>Note</th>
                                </tr>
                            </thead>
                            <tbody id="serviceDetailsTable"></tbody>
                        </table>
                        <div class="row mt-3 mb-3">
                            <div class="col-lg-4 mx-auto text-center">
                                <button id="sub-service-updateButton" class="btn btn-sm bg-theme text-light btn-outline-dark" style="display: none;">Update</button>
                                <button id="sub-service-cancelButton" class="btn btn-sm btn-outline-dark" style="display: none;">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Task to be assigned details start -->

                <!-- Todays deadline service details section start -->
                <div class="col-lg-12">
                    <div class="report-box border-theme sales-card p-4 mb-3 rounded-4 border-3" id="todaysdeadlineSection" style="display: none;">
                        <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                            Today's Deadline Work Details
                        </div>

                        <div class="container-fluid">
                            <div class="row mt-3">
                                <div class="col-md-3 text-center">
                                    <h5 class="mb-3">Client Name</h5>
                                    <input type="text" id="client_name3" class="form-control mt-2 text-center" readonly>
                                </div>
                                <div class="col-md-3 text-center">
                                    <h5 class="mb-3">Service</h5>
                                    <input type="text" id="service_name3" class="form-control mt-2 text-center" readonly>
                                </div>
                                <div class="col-md-3 text-center">
                                    <h5 class="mb-3">Manager</h5>
                                    <input type="text" id="manager_name3" class="form-control mt-2 text-center" value="" readonly>
                                </div>
                                <div class="col-md-3 text-center">
                                    <h5 class="mb-3">Frequency</h5>
                                    <input type="text" id="service_frequency3" class="form-control mt-2 text-center" readonly>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Sub Service Name</th>
                                                <th>Staff</th>
                                                <th>Note</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="todaysDeadlineServiceDetailsTable"></tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="row mt-3 mb-3">
                                <div class="col-lg-4 mx-auto text-center">
                                    <button id="todaysDeadline-cancelButton" class="btn btn-sm btn-outline-dark">Cancel</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Todays deadline service details section start -->

                <div class="row px-0 mx-auto">

                    <!-- Today's Deadline Start -->
                    <div class="col-lg-5">
                        <div class="col-lg-12 px-0 border shadow-sm mb-3">

                            <p class="p-2 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center fs-5">
                                <i class="bx bxs-user-plus fs-4 me-2"></i>Todays Deadline
                            </p>

                            <div class="table-wrapper my-4 mx-auto" style="width: 95%;">
                                <table table id="todaysDeadlineTable" class="table cell-border table-striped" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th scope="col">Client Name</th>
                                            <th scope="col">Service Name</th>
                                            <th scope="col">Action</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- Today's Deadline Start -->

                    <!-- Task to be asssigned -->
                    <div class="col-lg-7">
                        <div class="col-lg-12 px-0 border shadow-sm mb-3">
                            <p class="p-2 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center fs-5">
                                <i class="bx bxs-user-plus fs-4 me-2"></i>Task Need To Be Assigned
                            </p>
                            <div class="table-wrapper my-4 mx-auto" style="width: 95%;">
                                <table id="servicesTable" class="table cell-border table-striped" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Client Name</th>
                                            <th>Service Name</th>
                                            <th>Manager Name</th>
                                            <th>Deadline</th>
                                            <th>Frequency</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- Task to be asssigned -->

                </div>
            </div>

            <!-- Assigned service details section start -->
            <div class="col-lg-12">
                <div class="report-box border-theme sales-card p-4 mb-3 rounded-4 border-3" id="assignedTaskSection" style="display: none;">
                    <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                        Assigned Work Details
                    </div>

                    <div class="container-fluid">
                        <div class="row mt-3">
                            <div class="col-md-2 text-center">
                                <h5 class="mb-3">Client Name</h5>
                                <input type="text" id="client_name2" class="form-control mt-2 text-center" readonly>
                            </div>
                            <div class="col-md-2 text-center">
                                <h5 class="mb-3">Service</h5>
                                <input type="text" id="service_name2" class="form-control mt-2 text-center" readonly>
                            </div>
                            <div class="col-md-2 text-center">
                                <h5 class="mb-3">Manager</h5>
                                <input type="text" id="manager_name2" class="form-control mt-2 text-center" value="" readonly>
                            </div>
                            <div class="col-md-2 text-center">
                                <h5 class="mb-3">Frequency</h5>
                                <input type="text" id="service_frequency2" class="form-control mt-2 text-center" readonly>
                            </div>
                            <div class="col-md-2 text-center">
                                <h5 class="mb-3">Due Date</h5>
                                <span id="due_date2" class="form-control text-center" style="display: inline-block;"></span>
                            </div>
                            <div class="col-md-2 text-center">
                                <h5 class="mb-3">Deadline</h5>
                                <span id="service_deadline2" class="form-control text-center" style="display: inline-block;"></span>
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
                                        </tr>
                                    </thead>
                                    <tbody id="assignedServiceDetailsTable"></tbody>
                                </table>
                            </div>
                        </div>

                        <div class="row mt-3 mb-3">
                            <div class="col-lg-4 mx-auto text-center">
                                <button id="assigned-cancelButton" class="btn btn-sm btn-outline-dark">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Assigned service details section start -->

            <!-- Assigned Work List -->
            <div class="col-lg-12">
                <div class="col-lg-12 px-0 border shadow-sm mb-3">

                    <p class="p-2 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center fs-5">
                        <i class="bx bxs-user-plus fs-4 me-2"></i>Assigned Work List
                    </p>
                    <!-- Works assigned to a user and staff -->
                    <div class="table-wrapper my-4 mx-auto" style="width: 95%;">
                        <table id="assignedServices" class="table cell-border table-striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Client Name</th>
                                    <th scope="col">Service Name</th>
                                    <th scope="col">Due Date</th>
                                    <th scope="col">Target Deadline</th>
                                    <th scope="col">Deadline</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                    <!-- Works assigned to a user and staff -->
                </div>
            </div>
            <!-- Assigned Work List -->

            <!-- Completed service details section start -->
            <div class="col-lg-12">
                <div class="report-box border-theme sales-card p-4 mb-3 rounded-4 border-3" id="completedTaskSection" style="display: none;">
                    <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                        Completed Work Details
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
                                            <th>Timer</th>
                                            <th>Comment</th>
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

            <!-- Completed Work List -->
            <div class="col-lg-12">
                <div class="col-lg-12 px-0 border shadow-sm mb-3">

                    <p class="p-2 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center fs-5">
                        <i class="bx bxs-user-plus fs-4 me-2"></i>Completed Work List
                    </p>
                    <!-- Works assigned to a user and staff -->
                    <div class="table-wrapper my-4 mx-auto" style="width: 95%;">
                        <table id="completedServices" class="table cell-border table-striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Client Name</th>
                                    <th>Service Name</th>
                                    <th>Due Date</th>
                                    <th>Target Deadline</th>
                                    <th>Deadline</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <th scope="row">1</th>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Works assigned to a user and staff -->
                </div>
            </div>
            <!-- Completed Work List -->

            <!-- One time work details start -->
            <div class="col-lg-12">
                <div class="report-box border-theme sales-card p-4 mb-3 rounded-4 border-3" id="oneTimeWorkDetails" style="display: none;">
                    <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                        One Time Work Details
                    </div>

                    <div class="container-fluid">
                        <div class="row mt-3">
                            <div class="col-md-4 text-center">
                                <h5 class="mb-3">Service</h5>
                                <input type="text" id="oneTimeService" class="form-control mt-2 text-center" readonly>
                            </div>
                            <div class="col-md-4 text-center">
                                <h5 class="mb-3">Manager</h5>
                                <input type="text" id="oneTimeManager" class="form-control mt-2 text-center" value="" readonly>
                            </div>
                            <div class="col-md-4 text-center">
                                <h5 class="mb-3">Deadline</h5>
                                <input type="date" id="OneTimeDeadline" class="form-control mt-2 text-center" readonly>
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
                                            <th>Timer</th>
                                            <th>Comment</th>
                                        </tr>
                                    </thead>
                                    <tbody id="oneTimeWorkDetailsTable"></tbody>
                                </table>
                            </div>
                        </div>

                        <div class="row mt-3 mb-3">
                            <div class="col-lg-4 mx-auto text-center">
                                <button id="oneTimeWorkCancel" class="btn btn-sm btn-outline-dark">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- One time work details end  -->

            <div class="row">
                <!-- Assigned One Time Work List -->
                <div class="col-lg-6">
                    <div class="col-lg-12 px-0 border shadow-sm mb-3">

                        <p class="p-2 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center fs-5">
                            <i class="bx bxs-user-plus fs-4 me-2"></i>Assigned One Time Work List
                        </p>
                        <div class="table-wrapper my-4 mx-auto" style="width: 95%;">
                            <table id="assignedOneTimeServices" class="table cell-border table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th scope="col">Assign Date</th>
                                        <th scope="col">Task</th>
                                        <th scope="col">Deadline</th>
                                        <th scope="col">Assigned To</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Completed One Time Work List -->
                <div class="col-lg-6">
                    <div class="col-lg-12 px-0 border shadow-sm mb-3">

                        <p class="p-2 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center fs-5">
                            <i class="bx bxs-user-plus fs-4 me-2"></i>Completed One Time Work List
                        </p>
                        <div class="table-wrapper my-4 mx-auto" style="width: 95%;">
                            <table id="completedOneTimeServices" class="table cell-border table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th scope="col">Assign Date</th>
                                        <th scope="col">Task</th>
                                        <th scope="col">Deadline</th>
                                        <th scope="col">Assigned To</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-6 mb-3">
                    <div class="col-lg-12 px-0 border shadow-sm mb-3">
                            <p class="p-2 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center fs-5">
                                <i class="bx bxs-user-plus fs-4 me-2"></i>Your Notes
                            </p>
                            <div class="text-start my-3 mx-3">
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#note">
                                    Add New Note
                                </button>
                            </div>
                            <div class="table-wrapper my-4 mx-auto" style="width: 95%;">
                                <table id="notesTable" class="table cell-border table-striped" style="width: 100%;">
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

            <!-- Currently Active Staffs Start-->
            @if(count($loggedStaff) > 0)
            <div class="col-lg-12">
                <div class="col-lg-12 px-0 border shadow-sm mb-3">

                    <p class="p-2 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center fs-5">
                        <i class="bx bxs-user-plus fs-4 me-2"></i>Currently logged-in staffs
                    </p>

                    <div class="table-wrapper my-4 mx-auto" style="width: 95%;">
                        <table class="table cell-border table-striped" id="active-staff">
                            <thead>
                                <tr>
                                    <th scope="col" class="text-center">Name</th>
                                    <th scope="col" class="text-center">Login Time</th>
                                    <th scope="col" class="text-center">Attendence Status</th>
                                    <th scope="col" class="text-center">Duration</th>
                                    <th scope="col" class="text-center">Current Status</th>
                                    <th scope="col" class="text-center">Note</th>
                                    <th scope="col" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($loggedStaff as $staff)
                                <tr>
                                    <td class="text-center">{{ $staff->user->first_name }} {{ $staff->user->last_name }}</td>
                                    <td class="text-center">{{ \Carbon\Carbon::parse($staff->start_time)->format('H:i. d/m/Y') }}</td>
                                    <td class="text-center">
                                        @if ($staff->prorotaNotFound)
                                        Prorota not found
                                        @else
                                        @if ($staff->is_late)
                                        Late
                                        @else
                                        In Time
                                        @endif
                                        @endif
                                    </td>
                                    <td>
                                        <div class="text-center" id="duration_{{ $staff->id }}">{{ $staff->duration }}</div>
                                    </td>
                                    <td class="text-center">
                                        @if (isset($staff->current_status))
                                        @if ($staff->current_status == 'Logged In')
                                        <span class="badge bg-info">{{ $staff->current_status }}</span>
                                        @elseif ($staff->current_status == 'On Break')
                                        <span class="badge bg-warning">{{ $staff->current_status }}</span>
                                        @elseif ($staff->current_status == 'Working')
                                        <span class="badge bg-success">{{ $staff->current_status }}</span>
                                        @endif
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <textarea rows="2" name="note" placeholder="Add a note here..." style="border-radius: 5px;"></textarea>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-danger btn-sm logout-btn" data-staff-id="{{ $staff->id }}">Logout</button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
            @endif
            <!-- Currently Active Staffs End-->

            <!-- Todays's Late Staffs Start -->
            @if(count($lateStaff) > 0)
            <div class="col-lg-12">
                <div class="col-lg-12 px-0 border shadow-sm mb-3">
                    <p class="p-2 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center fs-5">
                        <i class="bx bxs-user-plus fs-4 me-2"></i>Late Staffs
                    </p>
                    <div class="table-wrapper my-4 mx-auto" style="width: 95%;">
                        <table class="table cell-border table-striped" id="late-staff-prorota">
                            <thead>
                                <tr>
                                    <th scope="col">Name</th>
                                    <th scope="col">Login Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($lateStaff as $staff)
                                <tr>
                                    <td>{{ $staff->user->first_name }} {{ $staff->user->last_name }}</td>
                                    <td>{{ \Carbon\Carbon::parse($staff->start_time)->format('H:i . d/m/Y') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
            <!-- Todays's Late Staffs End -->

            <!-- Todays's Shift Deviation Staffs Start -->
            @if(count($filteredLogs) > 0)
            <div class="col-lg-12">
                <div class="col-lg-12 px-0 border shadow-sm mb-3">
                    <p class="p-2 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center fs-5">
                        <i class="bx bxs-user-plus fs-4 me-2"></i>Departure Status
                    </p>
                    <div class="table-wrapper my-4 mx-auto" style="width: 95%;">
                        <table class="table cell-border table-striped" id="shift-deviation-staff">
                            <thead>
                                <tr>
                                    <th scope="col">Name</th>
                                    <th scope="col">Log Out Time</th>
                                    <th scope="col">Departure Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($filteredLogs as $index => $log)
                                <tr>
                                    <td>{{ $log->user->first_name }} {{ $log->user->last_name }}</td>
                                    <td>{{ \Carbon\Carbon::parse($log->end_time)->format('H:i. d/m/Y') }}</td>
                                    <td>{{ $log->departure_status }}</td>
                                </tr>
                                @endforeach

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
            <!-- Todays's Shift Deviation Staffs End -->

            <!-- Todays's Absent Staffs Start -->
            @if(count($absentStaff) > 0)
            <div class="col-lg-12">
                <div class="col-lg-12 px-0 border shadow-sm mb-3">
                    <p class="p-2 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center fs-5">
                        <i class="bx bxs-user-plus fs-4 me-2"></i>Today's Absent Staffs
                    </p>
                    <div class="table-wrapper my-4 mx-auto" style="width: 95%;">
                        <table class="table cell-border table-striped" id="absent-staff">
                            <thead>
                                <tr>
                                    <th scope="col">Name</th>
                                    <th scope="col">Comment</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($absentStaff as $index => $staff)
                                <tr>
                                    <td>{{ $staff->first_name }} {{ $staff->last_name }}</td>
                                    <td>
                                        @if ($staff->logComments->isNotEmpty())
                                        <textarea class="form-control comment-textarea" rows="1" readonly>{{ $staff->logComments->last()->comment }}</textarea>
                                        @else
                                        <textarea class="form-control comment-textarea" rows="1"></textarea>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($staff->logComments->isEmpty())
                                        <button class="btn btn-secondary submit-comment" data-staff-id="{{ $staff->id }}">Submit</button>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
            <!-- Todays's Absent Staffs End -->

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
                                @foreach($staffs as $employee)
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

</section>

@endif

@endsection

@section('script')

<script>
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
</script>

<script>

    $('#notesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/admin/get-note',
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
            url: '/admin/save-note',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                note: note
            },
            success: function (response) {
                toastr.success("Note saved!", "Success!");

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
            url: '/admin/assign-note',
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
                toastr.success("Note assigned successfully!", "Success!");

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
        $('.employee-section').addClass('d-none');
        $('.client-section').addClass('d-none');
        $('.btn-submit').addClass('d-none');
    });

</script>

<script>
    $(document).ready(function() {
        $('#assignedOneTimeServices').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/admin/get-one-time-assigned-service',
                type: 'GET',
                error: function(xhr, status, error) {
                    console.error('Error occurred:', xhr.responseText);
                }
            },
            columns: [{
                    data: 'created_at',
                    name: 'created_at',
                    render: function(data, type, row) {
                        return moment(data).format('DD.MM.YYYY');
                    }
                },
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
                    data: 'manager',
                    name: 'manager',
                    render: function(data, type, row) {
                        return data ? (data.first_name + ' ' + data.last_name) : 'N/A';
                    }
                }
            ]
        });

        $('#completedOneTimeServices').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/admin/get-one-time-completed-service',
                type: 'GET',
            },
            columns: [{
                    data: 'created_at',
                    name: 'created_at',
                    render: function(data, type, row) {
                        return moment(data).format('DD.MM.YYYY');
                    }
                },
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
                    data: 'manager',
                    name: 'manager',
                    render: function(data, type, row) {
                        return data ? (data.first_name + ' ' + data.last_name) : 'N/A';
                    }
                }
            ]
        });

        $(document).on('click', '.assigned-task-detail', function() {
            var clientserviceId = $(this).data('id');
            var managerFirstName = $(this).data('manager-firstname');
            var legal_deadline = $(this).data('manager-firstname');
            var rowData = $('#assignedOneTimeServices').DataTable().row($(this).closest('tr')).data();
            if (rowData) {
                var serviceName = rowData.servicename;
                var decodedServiceName = $('<div>').html(serviceName).text();
                $('#oneTimeService').val(decodedServiceName);
                $('#oneTimeManager').val(managerFirstName);
                $('#OneTimeDeadline').val(rowData.legal_deadline.original);

                $.ajax({
                    url: '/admin/getClientSubService/' + clientserviceId,
                    type: "GET",
                    dataType: "json",
                    success: function(data) {
                        populateOneTimeTaskForm(data);
                    },
                    error: function(xhr, error, thrown) {
                        console.error('Error fetching sub-services:', error, thrown);
                    }
                });

                $('#oneTimeWorkDetails').show();
            } else {
                console.error('Row data is undefined');
            }
        });

        function populateOneTimeTaskForm(subServices) {
            var oneTimeWork = $('#oneTimeWorkDetailsTable');
            oneTimeWork.empty();

            var staffs = @json($staffs);

            $.each(subServices, function(index, subService) {

                var staffName = subService.staff ? (subService.staff.first_name + ' ' + (subService.staff.last_name || '')).trim() : 'N/A';

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

                var newRow = `
                  <tr>
                      <td>${subService.sub_service.name}</td>
                      <td>${subService.deadline}</td>
                      <td>${staffName}</td>
                      <td>${subService.note ? subService.note : ''}</td>
                      <td>
                          ${  subService.sequence_status == 2 ? 'Work is completed' 
                              : subService.sequence_status == 1 ? 'Not Started' 
                              : subService.sequence_status == 0 ? 'Processing'
                              : 'N/A'
                          }
                      </td>
                      <td><span class="badge bg-success">${duration}</span></td>
                      <td>
                            <button type="button" class="btn btn-secondary open-modal" data-toggle="modal" data-target="#messageModal" data-client-sub-service-id="${subService.id}">
                                <i class="fas fa-plus-circle"></i>
                            </button>
                        </td>
                  </tr>
              `;
                oneTimeWork.append(newRow);
            });
        }

        $(document).on('click', '.open-modal', function() {
            var clientSubServiceId = $(this).data('client-sub-service-id');
            $('#hiddenClientSubServiceId').val(clientSubServiceId);
            populateMessage(clientSubServiceId);
            $('#messageModal').modal('show');
        });

        function populateMessage(clientSubServiceId) {
            $.ajax({
                url: '/admin/getServiceMessage/' + clientSubServiceId,
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

        $('#oneTimeWorkCancel').click(function() {
            $('#oneTimeWorkDetails').hide();
        });
    });
</script>
<!-- Assigned Work List -->

<!-- Assign service staff -->
<script>
    $(document).ready(function() {

        $('#myForm').submit(function(event) {
            event.preventDefault();

            var formData = new FormData($(this)[0]);
            $.ajax({
                url: "{{URL::to('/admin/assign-service-staff')}}",
                type: 'POST',
                data: formData,
                async: false,
                success: function(response) {
                    if (response.status === 200) {
                        $('#successMessage b').text(response.message);
                        $('#successMessage').show();
                        $('#errorMessage').hide();
                        $('#myForm')[0].reset();
                        window.location.reload();
                    } else {
                        $('#errorMessage b').text(response.message);
                        $('#errorMessage').show();
                        $('#successMessage').hide();
                        $('#myForm')[0].reset();
                    }
                },
                error: function(xhr, status, error) {
                    var errorMessage = "";
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        $.each(xhr.responseJSON.errors, function(key, value) {
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

        $('#clearButton').click(function() {
            $('#myForm')[0].reset();
            $('#errorMessage').hide();
            $('#successMessage').hide();
        });
    });

    $('#cancelButton').click(function() {
        $('#taskAssignForm').hide();
    });
</script>
<!-- Assign service staff -->

<!-- Processing + cancelled Work List + change status -->
<script>
    $(document).ready(function() {
        $('#serviceStaffTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/admin/get-services-client-staff',
                type: 'GET',
            },
            columns: [{
                    data: null,
                    name: 'id',
                    render: function(data, type, row, meta) {
                        return meta.row + 1;
                    }
                },
                {
                    data: 'deadline',
                    name: 'deadline',
                    render: function(data, type, row) {
                        return moment(data).format('DD.MM.YY');
                    }
                },
                {
                    data: 'client_name',
                    name: 'client_name'
                },
                {
                    data: 'tasks',
                    name: 'tasks'
                },
                {
                    data: 'staff_name',
                    name: 'staff_name'
                },
                {
                    data: 'status',
                    name: 'status',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        var options = '<select class="form-select btn btn-outline-secondary status-select" data-id="' + row.id + '">';
                        options += '<option value="1"' + (data === 1 ? ' selected' : '') + '>Processing</option>';
                        options += '<option value="2"' + (data === 2 ? ' selected' : '') + '>Completed</option>';
                        options += '<option value="3"' + (data === 3 ? ' selected' : '') + '>Cancelled</option>';
                        options += '</select>';
                        return options;
                    }
                }
            ]
        });

        $(document).on('change', '.status-select', function() {
            var serviceStaffId = $(this).data('id');
            var status = $(this).val();
            $.ajax({
                url: '/admin/update-service-status',
                type: 'POST',
                data: {
                    service_staff_id: serviceStaffId,
                    status: status,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        $('#serviceStaffTable').DataTable().ajax.reload();
                        toastr.success("Status changed successfully!", "Success!");
                    } else {
                        toastr.error("Failed to change status.", "Error!");
                    }
                },
                error: function(xhr, status, error) {}
            });
        });
    });
</script>
<!-- Processing + cancelled Work List -->

<!-- Completed Work List  -->
<script>
    $(document).ready(function() {
        $('#completedServices').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/admin/get-completed-services',
                type: 'GET',
            },
            columns: [
                {
                  data: 'DT_RowIndex',
                  name: 'DT_RowIndex',
                  orderable: false,
                  searchable: false
                },
                {
                    data: 'clientname',
                    name: 'clientname'
                },
                {
                    data: 'servicename',
                    name: 'servicename'
                },
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
                    data: 'is_admin_approved',
                    name: 'is_admin_approved',
                    render: function(data, type, row) {
                        return `
                            <select class="form-control approval-status-change" data-id="${row.id}">
                                <option value="1" ${data == 1 ? 'selected' : ''}>Completed</option>
                                <option value="0" ${data == 0 ? 'selected' : ''}>Processing</option>
                            </select>
                        `;
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

        $(document).on('click', '.task-details', function() {
            var clientserviceId = $(this).data('id');
            var managerFirstName = $(this).data('manager-firstname');
            var rowData = $('#completedServices').DataTable().row($(this).closest('tr')).data();
            var serviceName = rowData.servicename;
            var decodedServiceName = $('<div>').html(serviceName).text();
            var frequency = rowData.service_frequency;
            let deadline = rowData.service_deadline;
            let dueDate = rowData.due_date;
            let client = rowData.clientname;
            // deadline = deadline ? moment(deadline).format('YYYY-MM-DD') : '';

            $('#service_name1').val(decodedServiceName);
            $('#manager_name1').val(managerFirstName);
            $('#service_frequency1').val(frequency);
            $('#client_name1').val(client);
            $('#service_deadline1').text(deadline);
            $('#due_date1').text(dueDate);

            $.ajax({
                url: '/admin/getClientSubService/' + clientserviceId,
                type: "GET",
                dataType: "json",
                success: function(data) {
                    populateCompletedForm(data);
                    // console.log(data);
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
                    return staff.id === subService.staff_id;
                });

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
                    <select class="form-select change-service-status" data-sub-service-id="${subService.id}" ${subService.sequence_status == 0 || subService.sequence_status == 1 ? 'disabled' : ''}>
                        <option value="0" ${subService.sequence_status == 0 ? 'selected' : ''}>Processing</option>
                        <option value="1" ${subService.sequence_status == 1 ? 'selected' : ''}>Not Started</option>
                        <option value="2" ${subService.sequence_status == 2 ? 'selected' : ''}>Work is completed</option>
                    </select>`;

                const newMessageIcon = subService.has_new_message ?
                    '<span class="new-message-icon" style="color: red; margin-left: 5px;"><i class="fas fa-circle"></i></span>' :
                    '';

                var newRow = `
                    <tr>
                        <td>${subService.sub_service.name}</td>
                        <td>${subService.deadline}</td>
                        <td>${staffName}</td>
                        <td>${subService.note ? subService.note : ''}</td>
                         <td>${statusDropdown}</td>
                        <td>
                            <span class="badge bg-success">${duration}</span>
                        </td>
                        <td>
                            <button type="button" class="btn btn-secondary open-modal" data-toggle="modal" data-target="#messageModal" data-client-sub-service-id="${subService.id}">
                                <i class="fas fa-plus-circle"></i> ${newMessageIcon}
                            </button>
                        </td>
                    </tr>
                `;
                completedServiceDetailsTable.append(newRow);
            });

            $('#completedTaskSection').show();
        }

        $('#messageModal').on('hidden.bs.modal', function() {
            $('#assignedTaskSection, #completedTaskSection').hide();
        });

        $(document).on('change', '.change-service-status', function() {
            var clientSubServiceId = $(this).data('sub-service-id');
            var newStatus = $(this).val();
            // console.log(clientSubServiceId,newStatus)
            $.ajax({
                url: '/admin/update-sub-service-status',
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
                    toastr.success("Status changed successfully", "Success!");
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                },
                error: function(xhr, status, error) {
                    console.error(error);
                    var errorMessage = "";
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        $.each(xhr.responseJSON.errors, function(key, value) {
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

<!-- Assigned Work List  -->
<script>
    $(document).ready(function() {
        $('#assignedServices').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/admin/get-assigned-service',
                type: 'GET',
            },
            columns: [
                {
                  data: 'DT_RowIndex',
                  name: 'DT_RowIndex',
                  orderable: false,
                  searchable: false
                },
                {
                    data: 'clientname',
                    name: 'clientname'
                },
                {
                    data: 'servicename',
                    name: 'servicename',
                    render: function(data, type, row) {
                        let badge = '';
                        if (row.status == 1) {
                            badge = '<span class="badge bg-primary text-white">Not Started</span>';
                        } else if (row.status == 2) {
                            badge = '<span class="badge bg-success text-white">Completed</span>';
                        } else if (row.status == 0) {
                            badge = '<span class="badge bg-warning text-dark">Processing</span>';
                        } else {
                            badge = '<span class="badge bg-secondary text-white">Unknown</span>';
                        }
                        return `${data} <br>${badge}`;
                    }
                },
                {
                    data: 'due_date',
                    name: 'due_date'
                },
                {
                    data: 'legal_deadline',
                    name: 'legal_deadline',
                    render: function(data, type, row) {
                        if (!data.original) {
                            return 'N/A';
                        }

                        var formattedDate = data.formatted;
                        var today = moment().startOf('day');
                        var deadline = moment(data.original, 'DD-MM-YYYY').startOf('day');

                        if (row.status != 2 && deadline.isBefore(today)) {
                            return '<span class="bg-warning">' + formattedDate + '</span>';
                        }

                        return formattedDate;
                    }
                },
                {
                    data: 'service_deadline',
                    name: 'service_deadline',
                    render: function(data, type, row) {
                        if (!data.original) {
                            return 'N/A';
                        }

                        var formattedDate = data.formatted;

                        var today = moment().startOf('day');
                        var deadline = moment(data.original, 'DD-MM-YYYY').startOf('day');

                        if (row.status != 2 && deadline.isBefore(today)) {
                            return '<span class="bg-danger">' + formattedDate + '</span>';
                        }

                        return formattedDate;
                    }
                },
                {
                    data: 'is_admin_approved',
                    name: 'is_admin_approved',
                    render: function(data, type, row) {
                        return `
                            <select class="form-control approval-status-change" data-id="${row.id}">
                                <option value="1" ${data == 1 ? 'selected' : ''}>Completed</option>
                                <option value="0" ${data == 0 ? 'selected' : ''}>Processing</option>
                            </select>
                        `;
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

        $(document).on('change', '.approval-status-change', function() {
            const serviceId = $(this).data('id');
            const newStatus = $(this).val();
            $.ajax({
                url: '/admin/client-service-change-status',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: serviceId,
                    status: newStatus
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success("Status changed successfully!", "Success!");

                        if ($.fn.DataTable.isDataTable('#assignedServices')) {
                            $('#assignedServices').DataTable().ajax.reload(null, false);
                        }

                        if ($.fn.DataTable.isDataTable('#completedServices')) {
                            $('#completedServices').DataTable().ajax.reload(null, false);
                        }

                    } else {
                        toastr.error("An error occurred!", "Error!");
                    }
                },
                error: function(xhr, status, error) {
                    // console.log(xhr.responseText);
                    toastr.error("An error occurred!", "Error!");
                }
            });
        });

        $(document).on('click', '.task-detail', function() {
            var clientserviceId = $(this).data('id');
            var managerFirstName = $(this).data('manager-firstname');
            var rowData = $('#assignedServices').DataTable().row($(this).closest('tr')).data();

            if (rowData) {
                var serviceName = rowData.servicename;
                var frequency = rowData.service_frequency;
                let deadline = rowData.service_deadline;
                // deadline = deadline ? moment(deadline).format('YYYY-MM-DD') : '';
                // $('#service_name2').val(serviceName);
                var decodedServiceName = $('<div>').html(serviceName).text();
                let dueDate = rowData.due_date;
                let client = rowData.clientname;
                $('#service_name2').val(decodedServiceName);
                $('#manager_name2').val(managerFirstName);
                $('#service_frequency2').val(frequency);
                $('#service_deadline2').text(deadline.original);
                $('#due_date2').text(dueDate);
                $('#client_name2').val(client);

                $.ajax({
                    url: '/admin/getClientSubService/' + clientserviceId,
                    type: "GET",
                    dataType: "json",
                    success: function(data) {
                        populateCompletedForm(data);
                        // console.log(data);
                    },
                    error: function(xhr, error, thrown) {
                        console.error('Error fetching sub-services:', error, thrown);
                    }
                });

                $('#assignedTaskSection').show();
            } else {
                console.error('Row data is undefined');
            }
        });

        function populateCompletedForm(subServices) {
            var completedServiceDetailsTable = $('#assignedServiceDetailsTable');
            completedServiceDetailsTable.empty();

            var staffs = @json($staffs);

            $.each(subServices, function(index, subService) {

                var staffName = subService.staff ? (subService.staff.first_name + ' ' + (subService.staff.last_name || '')).trim() : 'N/A';

                const newMessageIcon = subService.has_new_message ?
                    '<span class="new-message-icon" style="color: red; margin-left: 5px;"><i class="fas fa-circle"></i></span>' :
                    '';

                var newRow = `
                  <tr>
                      <td>${subService.sub_service.name}</td>
                      <td>${subService.deadline}</td>
                      <td>${staffName}</td>
                      <td>${subService.note ? subService.note : ''}</td>
                      <td>
                          ${  subService.sequence_status == 2 ? 'Work is completed' 
                              : subService.sequence_status == 1 ? 'Not Started' 
                              : subService.sequence_status == 0 ? 'Processing'
                              : 'N/A'
                          }
                      </td>
                      <td>
                            <button type="button" class="btn btn-secondary open-modal" data-toggle="modal" data-target="#messageModal" data-client-sub-service-id="${subService.id}">
                                <i class="fas fa-plus-circle"></i> ${newMessageIcon}
                            </button>
                        </td>
                  </tr>
              `;
                completedServiceDetailsTable.append(newRow);
            });
        }

        $(document).on('click', '.open-modal', function() {
            var clientSubServiceId = $(this).data('client-sub-service-id');
            $('#hiddenClientSubServiceId').val(clientSubServiceId);
            populateMessage(clientSubServiceId);
            $('#messageModal').modal('show');
        });

        function populateMessage(clientSubServiceId) {
            $.ajax({
                url: '/admin/getServiceMessage/' + clientSubServiceId,
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

        $('#saveMessage').click(function() {
            var message = $('#service-message').val();
            var clientSubServiceId = $('#hiddenClientSubServiceId').val();

            $.ajax({
                url: '/admin/store-message',
                type: "POST",
                data: {
                    message: message,
                    client_sub_service_id: clientSubServiceId,
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    $('#service-message').val('');
                    populateMessage(clientSubServiceId);
                },
                error: function(xhr, status, error) {}
            });
        });

        $('#assigned-cancelButton').click(function() {
            $('#assignedTaskSection').hide();
        });
    });
</script>
<!-- Assigned Work List -->

<!-- Todays Deadline Work List  -->
<script>
    $(document).ready(function() {
        $('#todaysDeadlineTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/admin/get-todays-deadline-service',
                type: 'GET',
            },
            columns: [
                {
                    data: 'clientname',
                    name: 'clientname'
                },
                {
                    data: 'servicename',
                    name: 'servicename'
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                }
            ]
        });

        $(document).on('click', '.task', function() {
            var clientserviceId = $(this).data('id');
            var managerFirstName = $(this).data('manager-firstname');
            var rowData = $('#todaysDeadlineTable').DataTable().row($(this).closest('tr')).data();

            if (rowData) {
                var serviceName = rowData.servicename;
                var frequency = rowData.service_frequency;
                let deadline = rowData.service_deadline;
                deadline = deadline ? moment(deadline).format('YYYY-MM-DD') : '';
                clientName = rowData.clientname;
                var decodedServiceName = $('<div>').html(serviceName).text();

                $('#client_name3').val(clientName); 
                $('#service_name3').val(decodedServiceName);
                $('#manager_name3').val(managerFirstName);
                $('#service_frequency3').val(frequency);

                $.ajax({
                    url: '/admin/getClientSubService/' + clientserviceId,
                    type: "GET",
                    dataType: "json",
                    success: function(data) {
                        populateCompletedForm(data);
                        //   console.log(data);
                    },
                    error: function(xhr, error, thrown) {
                        console.error('Error fetching sub-services:', error, thrown);
                    }
                });

                $('#todaysdeadlineSection').show();
            } else {
                console.error('Row data is undefined');
            }
        });

        function populateCompletedForm(subServices) {
            var todaysDeadlineServiceDetailsTable = $('#todaysDeadlineServiceDetailsTable');
            todaysDeadlineServiceDetailsTable.empty();

            var staffs = @json($staffs);

            $.each(subServices, function(index, subService) {
                var staff = staffs.find(function(staff) {
                    return staff.id === subService.staff_id;
                });

                var staffName = subService.staff ? (subService.staff.first_name + ' ' + (subService.staff.last_name || '')).trim() : 'N/A';

                var newRow = `
                  <tr>
                      <td>${subService.sub_service.name}</td>
                      <td>${staffName}</td>
                      <td>${subService.note ? subService.note : ''}</td>
                      <td>
                          ${  subService.sequence_status == 2 ? 'Work is completed' 
                              : subService.sequence_status == 1 ? 'Not Started' 
                              : subService.sequence_status == 0 ? 'Processing'
                              : 'N/A'
                          }
                      </td>
                  </tr>
              `;
                todaysDeadlineServiceDetailsTable.append(newRow);
            });
        }

        $('#todaysDeadline-cancelButton').click(function() {
            $('#todaysdeadlineSection').hide();
        });
    });
</script>
<!-- Todays Deadline Work List -->

<!-- Task need to be assigned -->
<script>
    $(document).ready(function() {

        $('#servicesTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/admin/get-all-services',
                type: 'GET',
                dataSrc: 'data',
                error: function(xhr, error, thrown) {
                    console.error('DataTables error:', error, thrown);
                }
            },
            columns: [{
                    data: 'clientname',
                    name: 'clientname'
                },
                {
                    data: 'service.name',
                    name: 'service.name'
                },
                {
                    data: null,
                    name: 'manager',
                    render: function(data, type, row) {
                        return data.manager.first_name + ' ' + data.manager.last_name;
                    }
                },
                {
                    data: 'service_deadline',
                    name: 'service_deadline',
                    render: function(data, type, row) {
                        return data ? moment(data).format('DD-MM-YYYY') : '';
                    }
                },
                {
                    data: 'service_frequency',
                    name: 'service_frequency'
                },
                {
                    data: null,
                    name: 'action',
                    render: function(data, type, row) {
                        return '<button class="btn btn-secondary btn-sm assign-btn" ' +
                            'data-clientservice-id="' + data.id + '" ' +
                            'data-service-id="' + data.service.id + '" ' +
                            'data-client-service-id="' + data.client_service_id + '" ' +
                            'data-client-id="' + data.client.id + '" ' +
                            'data-client-name="' + data.client.name + '" ' +
                            'data-service-id="' + data.service.id + '" ' +
                            'data-service-name="' + data.service.name + '" ' +
                            'data-manager-id="' + data.manager.id + '" ' +
                            'data-manager-name="' + (data.manager.first_name + ' ' + data.manager.last_name) + '" ' +
                            'data-service-deadline="' + data.service_deadline + '"' +
                            'data-due_date="' + data.due_date + '"' +
                            'data-legal_deadline="' + data.legal_deadline + '"' +
                            'data-service-frequency="' + data.service_frequency + '">' +
                            'Assign</button>';
                    }
                }
            ]
        });

        function populateSubServiceForm(subServices, clientName, serviceName, managerName, serviceDeadline, serviceFrequency) {
            var subServiceTable = $('#serviceDetailsTable');
            subServiceTable.empty();

            $.each(subServices, function(index, subService) {
                var newRow = `
          <tr>
            <td>${subService.sub_service.name}</td>
            <input type="hidden" name="sub_service_id[]" value="${subService.sub_service_id}">
            <td>
              <input type="text" name="deadline[]" class="form-control subServiceDeadline" value="${subService.deadline ? subService.deadline : ''}">
            </td>
            <td>
              <select class="form-control staffDropdown" name="staff_id[]">
                <option value="">Select Staff</option>`;

                @foreach($staffs as $staff)
                newRow += `<option value="{{ $staff->id }}" ${subService.staff_id == {{$staff->id}} ? 'selected' : ''}>
                    {{ $staff->first_name }} {{ $staff->last_name }} ({{ $staff->type }})
                  </option>`;
                @endforeach

                newRow += `</select>
            </td>
            <td><textarea name="note[]" rows="1" class="form-control">${subService.note ? subService.note : ''}</textarea></td>
          </tr>
        `;
                subServiceTable.append(newRow);
            });
            $('.subServiceDeadline').datepicker({
                format: 'dd-mm-yyyy',
                autoclose: true,
                todayHighlight: true
            });
        }

        $('#servicesTable').on('click', '.assign-btn', function() {
            var clientserviceId = $(this).data('clientservice-id');
            var serviceId = $(this).data('service-id');
            var clientId = $(this).data('client-id');
            var clientName = $(this).data('client-name');
            var serviceName = $(this).data('service-name');
            var managerId = $(this).data('manager-id');
            var managerName = $(this).data('manager-name');
            var serviceDeadline = $(this).data('service-deadline');
            var due_date = $(this).data('due_date');
            var legal_deadline = $(this).data('legal_deadline');
            var serviceFrequency = $(this).data('service-frequency');


            $('#clientId').val(clientId);
            $('#servicesDropdown').val(serviceId);
            $('#managerDropdown').val(managerId);
            $('#service_frequency').val(serviceFrequency);
            $('#service_deadline').val(serviceDeadline);
            $('#legalDeadline').val(legal_deadline);
            $('#dueDate').val(due_date);

            $('#assignTaskSection').toggle();
            $('#sub-service-updateButton').show();
            $('#sub-service-cancelButton').show();

            $.ajax({
                url: '/admin/getClientSubServices/' + clientserviceId,
                type: "GET",
                dataType: "json",
                success: function(data) {
                    populateSubServiceForm(data, clientserviceId, serviceId, clientId, clientName, serviceName, managerId, managerName, serviceDeadline, serviceFrequency);
                },
                error: function(xhr, status, error) {
                    console.error(error);
                }
            });
        });

        $('#sub-service-cancelButton').click(function() {
            $('#assignTaskSection').hide();
            $('#serviceDetailsTable').empty();
            $('#sub-service-updateButton, #sub-service-cancelButton').hide();
        });
    });
</script>
<!-- Task need to be assigned -->

<!-- Task update / Assign start -->
<script>
    $(document).ready(function() {
        $('#sub-service-updateButton').click(function(e) {
            e.preventDefault();

            var clientId = $('#clientId').val();
            var serviceId = $('#servicesDropdown').val();
            var managerId = $('#managerDropdown').val();
            var serviceFrequency = $('#service_frequency').val();
            var serviceDeadline = $('#service_deadline').val();
            var dueDate = $('#dueDate').val();
            var legalDeadline = $('#legalDeadline').val();

            var subServices = [];
            $('#serviceDetailsTable tr').each(function() {
                var subServiceId = $(this).find('input[name="sub_service_id[]"]').val();
                var deadline = $(this).find('input[name="deadline[]"]').val();
                var staffId = $(this).find('select[name="staff_id[]"]').val();
                var note = $(this).find('textarea[name="note[]"]').val();

                subServices.push({
                    subServiceId: subServiceId,
                    deadline: deadline,
                    staffId: staffId,
                    note: note,
                    dueDate: dueDate,
                    legalDeadline: legalDeadline
                });
            });


            var data = {
                clientId: clientId,
                serviceId: serviceId,
                managerId: managerId,
                service_frequency: serviceFrequency,
                service_deadline: serviceDeadline,
                subServices: subServices
            };

            // console.log(data);

            $.ajax({
                url: '/admin/update-service-staff',
                type: 'POST',
                data: data,
                success: function(response) {
                    toastr.success("Task updated successfully", "Success!");
                    $('#assignTaskSection').hide();
                    $('#servicesTable').DataTable().ajax.reload();
                    $('#completedServices').DataTable().ajax.reload();
                    $('#serviceStaffTable').DataTable().ajax.reload();
                    $('#assignedServices').DataTable().ajax.reload();
                },
                error: function(xhr, status, error) {
                    console.error(error);
                    var errorMessage = "";
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        $.each(xhr.responseJSON.errors, function(key, value) {
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

        $('.dueDate, .legalDeadline, .serviceDeadline, .subServiceDeadline').datepicker({
            format: 'dd-mm-yyyy',
            autoclose: true,
            todayHighlight: true
        });
    });
</script>
<!-- Task update / Assign end -->

<!-- Fetching sub services and putting on table start -->
<script>
    $(document).ready(function() {
        $('#servicesDropdown').change(function() {
            var serviceId = $(this).val();
            if (serviceId) {
                $.ajax({
                    url: '/admin/getSubServices/' + serviceId,
                    type: "GET",
                    dataType: "json",
                    success: function(data) {
                        $('#serviceDetailsTable tr').remove();

                        $.each(data, function(key, value) {
                            var newRow = `
                                <tr>
                                    <td>${value.name}</td>
                                    <td><input type="date" name="deadline" class="form-control"></td>
                                    <td>
                                        <select class="form-control staffDropdown" name="staff_id">
                                            <option value="">Select Staff</option>
                                            @foreach($staffs as $staff)
                                                <option value="{{ $staff->id }}">{{ $staff->first_name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><textarea name="note" rows="1" class="form-control"></textarea></td>
                                <input type="hidden" class="sub-service-id" data-sub-service-id="${value.id}">
                                </tr>
                            `;
                            $('#serviceDetailsTable').append(newRow);
                        });
                        $('#subServicesDropdown').show();
                    }
                });
            } else {
                $('#subServicesDropdown').empty().hide();
            }
        });
    });
</script>
<!-- Fetching sub services and putting on table end -->

<!-- Comment On absent staffs start -->
<script>
    $(document).ready(function() {
        $('.submit-comment').click(function() {
            var staffId = $(this).data('staff-id');
            var comment = $(this).closest('tr').find('.comment-textarea').val();

            $.ajax({
                type: 'POST',
                url: '{{ route("add.comment") }}',
                data: {
                    user_id: staffId,
                    comment: comment,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    toastr.success("Note sent successfully", "Success!");
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                },
                error: function(xhr, status, error) {
                    (xhr.responseText);
                }
            });
        });
    });
</script>
<!-- Comment On absent staffs start -->

<!-- Data table initialize -->
<script>
    $(document).ready(function() {
        $("#active-staff, #late-staff-prorota, #absent-staff, #shift-deviation-staff").DataTable();
    });
</script>
<!-- Data table initialize -->

<!-- Logout staff start -->
<script>
    $(document).ready(function() {
        $('.logout-btn').click(function() {
            const attendenceId = $(this).data('staff-id');
            const note = $(this).closest('tr').find('textarea[name="note"]').val();

            $.ajax({
                url: '/admin/staff-logout/' + attendenceId,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                data: {
                    note: note
                },
                success: function(response) {
                    toastr.success("Staff logged out successfully", "Success!");
                    window.setTimeout(function() {
                        location.reload()
                    }, 2000);
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                }
            });
        });
    });
</script>
<!-- Logout staff end -->

<!-- Duration Time start -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        function updateActiveTime(element) {
            var currentTime = element.textContent.trim();

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
            element.textContent = formattedTime;
        }

        function startUpdatingTimes() {
            var elements = document.querySelectorAll('[id^="duration_"]');

            elements.forEach(function(element) {
                setInterval(function() {
                    updateActiveTime(element);
                }, 1000);
            });
        }

        startUpdatingTimes();
    });
</script>
<!-- Duration Time end -->

@endsection