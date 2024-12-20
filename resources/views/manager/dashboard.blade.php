@extends('manager.layouts.manager')

@section('content')

<section class="section dashboard" id="breakSection">
        <div class="row">

          <!-- Assigned service details section start -->
          <div class="col-lg-12">
              <div class="report-box border-theme sales-card p-4 mb-3 rounded-4 border-3" id="assignTaskSection" style="display: none;">
                  <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                       Work Details
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
                          <div class="col-md-3 text-center">
                              <h5 class="mb-3">Service</h5>
                              <input type="text" id="service_name" class="form-control mt-2 text-center" readonly>
                          </div>    
                          <div class="col-md-3 text-center">
                              <h5 class="mb-3">Manager</h5>
                              <input type="text" id="manager_name" class="form-control mt-2 text-center" value="" readonly>
                          </div>  
                          <div class="col-md-3 text-center">
                              <h5 class="mb-3">Frequency</h5>
                              <input type="text" id="service_frequency" class="form-control mt-2 text-center" readonly>
                          </div>   
                          <div class="col-md-3 text-center">
                              <h5 class="mb-3">Deadline</h5>
                              <input type="date" id="service_deadline" class="form-control mt-2 text-center" readonly>
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

         {{--
        <div class="col-lg-4 mb-3">
            <div class="report-box border-theme sales-card p-4 rounded-4 border-3 h-100 position-relative">
                <div class="card-body px-0">
                    <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                        Your Notes
                    </div>
                    <div class="mh250">
                        <!-- Your notes content here -->
                    </div>
                </div>
            </div>
        </div>
        --}}


        <!-- Login Time and button -->
        <div class="col-lg-4 mb-3">
            <div class="report-box border-theme sales-card p-4 rounded-4 border-3 h-100">
                <div class="card-body p-0">
                    <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                        Your Time
                    </div>
                    <div class="d-flex gap-3 my-5">
                        <div class="text-center flex-fill">
                            <div class="fs-6 txt-theme fw-bold">Active Time</div>
                            <div class="container">
                                <div class="text-center fs-2 txt-theme fw-bold" id="activeTime">
                                    {{ $activeTimeFormatted ?? 'N/A' }}
                                </div>
                            </div>
                        </div>
                        {{-- <div class="text-center border-start border-3 ps-3 flex-fill">
                            <div class="fs-6 txt-theme fw-bold">Break Time</div>
                            <div class="text-center fs-2 txt-theme fw-bold">
                                   {{ $breakTime ?? 'N/A' }}
                            </div>
                        </div> --}}
                    </div>
                    <div class="row mt-3 align-items-center justify-content-center">
                        {{--  <div class="col-lg-6">
                            <a href="" class="p-2 border-theme text-center fs-6 d-block rounded-3 border-3 txt-theme fw-bold my-1">Clock in</a>
                        </div> --}}
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
        <!-- Login Time and button -->

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
                                            Your tasks
                                        </div>
                                    
                                        <div class="mt-3">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Active Time:</th>
                                                        <th>Break Time:</th>
                                                        <th>Total Work Time:</th>
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
                                                        <th>Client Name</th>
                                                        <th>Sub Service Name</th>
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

        <!-- Works assigned to a user and specified staff start-->
        <div class="col-lg-8 mb-3">
            <div class="report-box border-theme sales-card p-4 rounded-4 border-3 h-100 position-relative">
                <div class="card-body px-0">
                    <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                        Your Assigned Tasks
                    </div>
                        <div class="table-wrapper my-4 mx-auto" style="width: 95%;">
                        <table id="serviceManagerTable" class="table cell-border table-striped" style="width:100%">
                            <thead>
                                <tr>
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
                </div>
            </div>
        </div>
        <!-- Works assigned to a user and specified staff start-->

        <!-- Completed service details section start -->
         <div class="col-lg-12">
              <div class="report-box border-theme sales-card p-4 mb-3 rounded-4 border-3" id="completedTaskSection" style="display: none;">
                  <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                       Completed Work Details
                    </div>

                  <div class="container-fluid">
                      <div class="row mt-3">
                          <div class="col-md-3 text-center">
                              <h5 class="mb-3">Service</h5>
                              <input type="text" id="service_name1" class="form-control mt-2 text-center" readonly>
                          </div>    
                          <div class="col-md-3 text-center">
                              <h5 class="mb-3">Manager</h5>
                              <input type="text" id="manager_name1" class="form-control mt-2 text-center" readonly>
                          </div>  
                          <div class="col-md-3 text-center">
                              <h5 class="mb-3">Frequency</h5>
                              <input type="text" id="service_frequency1" class="form-control mt-2 text-center" readonly>
                          </div>   
                          <div class="col-md-3 text-center">
                              <h5 class="mb-3">Deadline</h5>
                              <input type="date" id="service_deadline1" class="form-control mt-2 text-center" readonly>
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
                              <button id="completed-service-cancelButton" class="btn btn-sm btn-outline-dark">Cancel</button>
                          </div>
                      </div>
                  </div>
              </div>
         </div>
        <!-- Completed service details section end -->

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
                                    <th scope="col">Deadline</th>
                                    <th scope="col">Frequency</th>
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
</section>

<div class="col-lg-4 mb-3" id="breakOutSection" style="display: none;">
    <div class="report-box border-theme sales-card p-4 rounded-4 border-3 h-100">
        <div class="card-body p-0">
            <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold" style="margin-bottom: 35px;">
            Clock Out to Start Work 
        </div>

            <!-- Add some blank space -->
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

<!-- Assigned tasks list start -->
<script>
    $(document).ready(function() {
        $('#serviceManagerTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/manager/get-all-services',
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
                    name: 'due_date',
                    render: function(data, type, row) {
                        return moment(data).format('DD.MM.YY');
                    }
                },
                { 
                    data: 'legal_deadline', 
                    name: 'legal_deadline',
                    render: function(data, type, row) {
                        return moment(data).format('DD.MM.YY');
                    }
                },
                { 
                    data: 'service_deadline', 
                    name: 'service_deadline',
                    render: function(data, type, row) {
                        return moment(data).format('DD.MM.YY');
                    }
                },
                {
                    data: 'status',
                    name: 'status',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        var statusOptions = {
                            0: 'Processing',
                            2: 'Completed'
                        };
                        var select = '<select class="form-control status-change" data-id="' + row.id + '">';
                        for (var value in statusOptions) {
                            var selected = (data == value) ? 'selected' : '';
                            select += '<option value="' + value + '" ' + selected + '>' + statusOptions[value] + '</option>';
                        }
                        select += '</select>';
                        return select;
                    }
                },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ]
        });

        $(document).on('change', '.status-change', function() {
            var newStatus = $(this).val();
            var itemId = $(this).data('id');

            swal({
                title: "Change Status?",
                text: "Are you sure you want to change the status?",
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then((result) => {
                if (result) {
                    $.ajax({
                        type: 'POST',
                        url: '/manager/client-service-change-status',
                        data: {
                            id: itemId,
                            status: newStatus,
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                swal("Success!", response.message, "success");
                                setTimeout(function() {
                                    location.reload();
                                }, 2000);
                            } else {
                                swal("Error!", response.message, "error");
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error(xhr.responseText);
                        }
                    });
                }
            });
        });

        $(document).on('click', '.change-status', function() {
            var clientserviceId = $(this).data('id');
            var managerFirstName = $(this).data('manager-firstname');
            var rowData = $('#serviceManagerTable').DataTable().row($(this).closest('tr')).data();
            var serviceName = rowData.servicename;
            var frequency = rowData.service_frequency;
            var deadline = rowData.service_deadline;

            $('#service_name').val(serviceName);
            $('#manager_name').val(managerFirstName);
            $('#service_frequency').val(frequency);
            $('#service_deadline').val(deadline);

            $.ajax({
                url: '/manager/getClientSubServices/' + clientserviceId,
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
                    return staff.id === subService.staff_id;
                });

                if (subService.sequence_status === 0 || subService.sequence_status === 1) {
                    staffDropdown = '<select class="form-select change-staff" data-sub-service-id="' + subService.id + '">';
                    staffs.forEach(function(staffMember) {
                        staffDropdown += '<option value="' + staffMember.id + '" ' + (staffMember.id === subService.staff_id ? 'selected' : '') + '>' + staffMember.first_name + '</option>';
                    });
                    staffDropdown += '</select>';
                } else {
                    staffDropdown = staff ? staff.first_name : 'N/A';
                }

                var staffName = staff ? staff.first_name : 'N/A';
                var isAuthUserStaff = authUserId === subService.staff_id;
                var hasWorkTimes = subService.work_times && subService.work_times.length > 0;

                if (subService.sequence_status === 0) {
                    if (isAuthUserStaff && hasWorkTimes) {
                        statusDropdown = `
                            <select class="form-select change-service-status" data-sub-service-id="${subService.id}">
                                <option value="0" selected>Processing</option>
                                <option value="2">Completed</option>
                            </select>`;
                    } else {
                        statusText = 'Processing';
                    }
                } else if (subService.sequence_status === 1) {
                    statusText = 'Work isn\'t started yet';
                } else if (subService.sequence_status === 2) {
                    statusText = 'Work is completed';
                }

                var startButton = '';
                var stopButton = '';
                var startBreakButton = '';
                var stopBreakButton = '';
                var duration = '';
                var firstWorkTime = subService.work_times.filter(workTime => workTime.is_break === 0)
                    .reduce(function(acc, workTime) {
                            return acc + parseInt(workTime.duration);
                        }, 0);
                
                if (subService.sequence_status === 2) {
                    if (firstWorkTime) {
                        var durationInSeconds = firstWorkTime;
                        var hours = Math.floor(durationInSeconds / 3600);
                        var minutes = Math.floor((durationInSeconds % 3600) / 60);
                        var seconds = durationInSeconds % 60;
                        duration = `<div>${hours}h ${minutes}m ${seconds}s</div>`;
                    }
                }

                if (isAuthUserStaff && subService.sequence_status === 0) {
                    if (subService.status === 2) {
                        startBreakButton = `<button type="button" class="btn btn-secondary start-break" data-sub-service-id="${subService.id}">Start Break</button>`;
                        stopButton = `<button type="button" class="btn btn-secondary stop-timer" data-sub-service-id="${subService.id}">Stop</button>`;
                        
                    } else if (subService.status === 3) {
                        if (subService.work_times) {
                            subService.work_times.forEach(function(work_time) {
                                stopBreakButton = `<button type="button" class="btn btn-danger stop-break" data-sub-service-id="${subService.id}" data-work-times-id="${work_time.id}">Stop Break</button>`;
                            });
                        }
                    } else if (subService.status === 1) {
                        startButton = `<button type="button" class="btn btn-secondary start-timer" data-sub-service-id="${subService.id}">Start</button>`;
                    }
                }

                var newRow = `
                    <tr>
                        <td>${subService.sub_service.name}</td>
                        <td>${moment(subService.deadline).format('DD.MM.YYYY')}</td>
                        <td>${staffDropdown}</td>
                        <td>${subService.note ? subService.note : ''}</td>
                        <td>${statusText} ${statusDropdown}</td>
                        <td>
                            <button type="button" class="btn btn-secondary open-modal" data-toggle="modal" data-target="#messageModal" data-staff-id="${subService.staff_id}" data-client-sub-service-id="${subService.id}">
                                <i class="fas fa-plus-circle"></i>
                            </button>
                        </td>
                        <td>
                            ${startButton}
                            ${stopButton}
                            {{--${startBreakButton}
                            ${stopBreakButton}--}}
                            ${duration}
                        </td>
                    </tr>
                `;
                subServiceTable.append(newRow);
            });

            $('#assignTaskSection').show();
        }

        $(document).on('change', '.change-staff', function() {
            var clientSubServiceId = $(this).data('sub-service-id');
            var newStaffId = $(this).val();

            $.ajax({
                url: '/manager/update-sub-service-staff',
                type: 'POST',
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    clientSubServiceId: clientSubServiceId,
                    newStaffId: newStaffId
                },
                success: function(response) {
                    swal({
                        title: "Success!",
                        text: "Staff changed successfully",
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
        });


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
                url: '/manager/getServiceMessage/' + clientSubServiceId,
                type: "GET",
                dataType: "json",
                success: function(data) {
                    var managers = @json($managers);
                    function getManagerName(managerId) {
                        var manager = managers.find(manager => String(manager.id) === String(managerId));
                        return manager ? manager.first_name : '';
                    }
                    $('#previousMessages').empty();
                    data.forEach(function(message) {
                        var messageDiv = $('<div>').addClass('message');
                        var managerName = getManagerName(message.created_by);
                        var messageContent = message.message ? message.message : ''; 
                        messageDiv.html('<span style="font-weight: bold;">' + managerName + ': </span>' + messageContent); 
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
            var staffId = $('#hiddenStaffId').val();
            var clientSubServiceId = $('#hiddenClientSubServiceId').val(); 

            $.ajax({
                url: '/manager/store-message',
                type: "POST",
                data: {
                    message: message,
                    staff_id: staffId,
                    client_sub_service_id: clientSubServiceId,
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    swal({
                        title: "Success!",
                        text: "Comment sent successfully",
                        icon: "success",
                        button: "OK",
                    });
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                    $('#messageModal').modal('hide'); 
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
                url: '/manager/update-sub-service-status',
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
                    swal({
                        title: "Success!",
                        text: "Status chnaged successfully",
                        icon: "success",
                        button: "OK",
                    });
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
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
                url: '/manager/start-work-time',
                data: {
                    clientSubServiceId: clientSubServiceId,
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    swal({
                        title: "Success!",
                        text: "Time has started successfully",
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
        }); 

        $(document).on('click', '.stop-timer', function() {
            var clientSubServiceId = $(this).data('sub-service-id');
            $.ajax({
                type: 'POST',
                url: '/manager/stop-work-time',
                data: {
                    clientSubServiceId: clientSubServiceId,
                    _token: "{{ csrf_token() }}" 
                },
                success: function(response) {
                    swal({
                        title: "Success!",
                        text: "Time has been stopped successfully",
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
        });

        $(document).on('click', '.start-break', function() {
            var clientSubServiceId = $(this).data('sub-service-id');
            $.ajax({
                type: 'POST',
                url: '/manager/start-break',
                data: {
                    clientSubServiceId: clientSubServiceId,
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    swal({
                        title: "Success!",
                        text: "Break time has started successfully",
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
        });

        $(document).on('click', '.stop-break', function() {
            var clientSubServiceId = $(this).data('sub-service-id');
            var workTimesId = $(this).data('work-times-id');
            $.ajax({
                type: 'POST',
                url: '/manager/stop-break',
                data: {
                    clientSubServiceId: clientSubServiceId,
                     workTimesId: workTimesId,
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    swal({
                        title: "Success!",
                        text: "Break time has stopped successfully",
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
        });
    });
</script>
<!-- Assigned tasks list start -->

<!-- Completed tasks list start -->
<script>
    $(document).ready(function() {
        $('#completedTasksTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/manager/get-completed-services',
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
                    data: 'service_deadline', 
                    name: 'service_deadline',
                    render: function(data, type, row) {
                        return moment(data).format('DD.MM.YY');
                    }
                },
                { data: 'service_frequency', name: 'service_frequency' },
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
            var managerName = $(this).data('manager');
            var rowData = $('#completedTasksTable').DataTable().row($(this).closest('tr')).data();
            var serviceName = rowData.servicename;
            var frequency = rowData.service_frequency;
            var deadline = rowData.service_deadline;

            $('#service_name1').val(serviceName);
            $('#manager_name1').val(managerName);
            $('#service_frequency1').val(frequency);
            $('#service_deadline1').val(deadline);

            $.ajax({
                url: '/manager/getClientSubServices/' + clientserviceId,
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
                    return staff.id === subService.staff_id;
                });

                var authUserId = {{ auth()->user()->id }};
                var isAuthUserStaff = authUserId === subService.staff_id;
                var staffName = staff ? staff.first_name : 'N/A';
                var duration = '';
                var firstWorkTime = subService.work_times.filter(workTime => workTime.is_break === 0)
                    .reduce(function(acc, workTime) {
                            return acc + parseInt(workTime.duration);
                        }, 0);

                if (subService.sequence_status === 2) {
                    if (firstWorkTime) {
                        var durationInSeconds = firstWorkTime;
                        var hours = Math.floor(durationInSeconds / 3600);
                        var minutes = Math.floor((durationInSeconds % 3600) / 60);
                        var seconds = durationInSeconds % 60;
                        duration = `<div>${hours}h ${minutes}m ${seconds}s</div>`;
                    }
                }

                var statusDropdown = `
                    <select class="form-select change-service" data-sub-service-id="${subService.id}" ${isAuthUserStaff && subService.sequence_status === 2 ? '' : 'disabled'}>
                        <option value="0" ${subService.sequence_status === 0 ? 'selected' : ''}>Processing</option>
                        <option value="2" ${subService.sequence_status === 2 ? 'selected' : ''}>Work is completed</option>
                    </select>`;

                var newRow = `
                    <tr>
                        <td>${subService.sub_service.name}</td>
                        <td>${moment(subService.deadline).format('DD.MM.YYYY')}</td>
                        <td>${staffName}</td>
                         <td>${subService.note ? subService.note : ''}</td>
                        <td>${statusDropdown}</td>
                        <td>
                            <button type="button" class="btn btn-secondary open-modal" data-toggle="modal" data-target="#messageModal" data-staff-id="${subService.staff_id}" data-client-sub-service-id="${subService.id}">
                                <i class="fas fa-plus-circle"></i>
                            </button>
                        </td>
                         <td>
                            <span class="timer-duration">${duration}</span>
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
                url: '/manager/change-sub-service-status',
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
                    swal({
                        title: "Success!",
                        text: "Status chnaged successfully",
                        icon: "success",
                        button: "OK",
                    });
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
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

        $('#completed-service-cancelButton').click(function() {
            $('#completedTaskSection').hide();
        });
    });
</script>
<!-- Completed tasks list end -->

<!-- Task Check before loggin out start -->
<script>
    function checkWorkTimeStatus() {
        $.ajax({
            url: '/manager/check-work-time-status', 
            type: 'GET',
            success: function(response) {
                if (response.status === 'ongoing') {
                     swal({
                        title: "Warning!",
                        text: "Please stop your break or work time before logging out.",
                        icon: "warning",
                        button: "OK",
                    });
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

<!-- Take Break Start -->
<script>
    $(document).ready(function() {
        checkBreakStatus($('#workTimeId').val());

        $('#takeBreakBtn').click(function(event) {
            event.preventDefault();

            $.ajax({
                url: '/manager/take-break',
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
                    url: '/manager/break-out',
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
                url: '/manager/check-break-status',
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
            url: '/manager/get-completed-services-modal',
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

    $(document).ready(function() {
        fetchClientSubServices();
    });
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
                            <select class="form-control px-3 py-2 client-name select2" style="width: 115px;">
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}">{{ $client->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex flex-column">
                            <label>Sub Service:</label>
                            <select class="form-control px-3 py-2 sub-service-name select2" style="width: 115px;">
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
            $('.select2').select2();
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
                url: '/manager/save-notes',
                type: 'POST',
                data: formData,
                success: function(response) {
                    swal({
                        title: "Success!",
                        text: "Record saved and you will be logged out now",
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
        });
    });
</script>
<!-- Note and additional work end -->

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