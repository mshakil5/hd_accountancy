@extends('staff.layouts.staff')

@section('content')

<section class="section dashboard">
    <div class="row">

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

        <div class="col-lg-4 mb-3">
            <div class="report-box border-theme sales-card p-4 rounded-4 border-3 h-100">
                <div class="card-body p-0">
                    <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                        Your Time
                    </div>
                    <div class="d-flex gap-3 my-5">
                        <div class="text-center flex-fill">
                            <div class="fs-6 txt-theme fw-bold">Active Time</div>
                            <div class="text-center fs-2 txt-theme fw-bold">
                                {{ $activeTime ?? 'N/A' }}
                            </div>
                        </div>
                        <div class="text-center border-start border-3 ps-3 flex-fill">
                            <div class="fs-6 txt-theme fw-bold">Break Time</div>
                            <div class="text-center fs-2 txt-theme fw-bold">
                                   {{ $breakTime ?? 'N/A' }}
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-lg-6">
                            <a href="" class="p-2 border-theme text-center fs-6 d-block rounded-3 border-3 txt-theme fw-bold my-1">Clock in</a>
                        </div>
                        <div class="col-lg-6">
                            <a href="" class="p-2 border-theme text-center fs-6 d-block rounded-3 border-3 txt-theme fw-bold my-1">Take Break</a>
                        </div>
                    </div>
                   <div class="row mt-3">
                        <div class="col-lg-12">
                            <a href="#" onclick="checkWorkTimeStatus();" class="p-2 border-theme bg-theme text-center fs-6 d-block rounded-3 border-3 text-light fw-bold">Clock out</a>
                            <form id="logout-form" action="{{ route('customLogout') }}" method="POST" class="d-none">
                                @csrf
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="noteModal" tabindex="-1" aria-labelledby="noteModalLabel" aria-hidden="true">
            <div class="modal-dialog mt-2">
                <div class="modal-content">
                    <div class="modal-body">

                        <div class="report-box border-theme sales-card p-4 rounded-4 border-3 h-100 position-relative">
                            <div class="card-body px-0">
                                <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                                    Add Note
                                </div>

                                <form id="noteForm" method="POST" action="{{ route('customLogout') }}">
                                    @csrf
                                    <div class="form-group">
                                        <div class="form-group mt-4">
                                            <label for="noteInput">Note:</label>
                                            <textarea class="form-control" id="noteInput" rows="5" name="note" placeholder="Your notes..."></textarea>
                                        </div>
                                    </div>
                                    <input type="hidden" name="_method" value="POST">
                                </form>

                                <div class="text-center">
                                    <button type="button" class="mt-3 btn btn-primary bg-theme-light fs-4 border-theme border-2 fw-bold txt-theme" id="saveNoteBtn">Save Note</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8 mb-3">
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
    </div>

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
                        <input type="text" id="service_name" class="form-control mt-2" readonly>
                    </div>    
                    <div class="col-md-3 text-center">
                        <h5 class="mb-3">Manager</h5>
                        <input type="text" id="manager_name" class="form-control mt-2" value="" readonly>
                    </div>  
                    <div class="col-md-3 text-center">
                        <h5 class="mb-3">Frequency</h5>
                        <input type="text" id="service_frequency" class="form-control mt-2" readonly>
                    </div>   
                    <div class="col-md-3 text-center">
                        <h5 class="mb-3">Deadline</h5>
                        <input type="date" id="service_deadline" class="form-control mt-2" readonly>
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

    <div class="row">
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
        <div class="col-lg-8 mb-3">
            <div class="report-box border-theme sales-card p-4 rounded-4 border-3 h-100 position-relative">
                <div class="card-body px-0">
                    <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                        Your Assigned Tasks
                    </div>
                <!-- Works assigned to a user and specified staff -->
                        <div class="table-wrapper my-4 mx-auto" style="width: 95%;">
                        <table id="serviceStaffTable" class="table cell-border table-striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th scope="col">Client Name</th>
                                    <th scope="col">Service Name</th>
                                    <th scope="col">Deadline</th>
                                    <th scope="col">Frequency</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <th scope="row">1</th>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                        </div>
                <!-- Works assigned to a user and specified staff -->
                </div>
            </div>
        </div>

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
                            <input type="text" id="service_name1" class="form-control mt-2" readonly>
                        </div>    
                        <div class="col-md-3 text-center">
                            <h5 class="mb-3">Manager</h5>
                            <input type="text" id="manager_name1" class="form-control mt-2" value="" readonly>
                        </div>  
                        <div class="col-md-3 text-center">
                            <h5 class="mb-3">Frequency</h5>
                            <input type="text" id="service_frequency1" class="form-control mt-2" readonly>
                        </div>   
                        <div class="col-md-3 text-center">
                            <h5 class="mb-3">Deadline</h5>
                            <input type="date" id="service_deadline1" class="form-control mt-2" readonly>
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

@endsection

@section('script')

<script>
    var authId = @json(auth()->id());
</script>

<!-- Assigned Work List -->
<script>
    $(document).ready(function() {
      $('#serviceStaffTable').DataTable({
          processing: true,
          serverSide: true,
          ajax: {
              url: '/staff/get-all-services',
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
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ]
        });

        $(document).on('click', '.change-status', function() {
            var clientserviceId = $(this).data('id');
            var managerFirstName = $(this).data('manager-firstname');
            var rowData = $('#serviceStaffTable').DataTable().row($(this).closest('tr')).data();
            var serviceName = rowData.servicename;
            var frequency = rowData.service_frequency;
            var deadline = rowData.service_deadline;

            $('#service_name').val(serviceName);
            $('#manager_name').val(managerFirstName);
            $('#service_frequency').val(frequency);
            $('#service_deadline').val(deadline);

            $.ajax({
                url: '/staff/getClientSubServices/' + clientserviceId,
                type: "GET",
                dataType: "json",
                success: function(data) {
                    //  console.log(data);
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

                var staffName = staff ? staff.first_name : 'N/A';
                var isAuthUserStaff = authUserId === subService.staff_id;

                if (subService.sequence_status === 0) {
                    if (isAuthUserStaff) {
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
                var firstWorkTime = subService.work_times[0];

                  if (subService.sequence_status === 2) {
                    var firstWorkTime = subService.work_times[0];
                    if (firstWorkTime) {
                        var durationInSeconds = firstWorkTime.duration;
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
                        <td>${staffName}</td>
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
                            ${startBreakButton}
                            ${stopBreakButton}
                            ${duration}
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
                url: '/staff/store-message',
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
                    url: '/staff/start-work-time',
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
                url: '/staff/stop-work-time',
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
            console.log(clientSubServiceId);
            $.ajax({
                type: 'POST',
                url: '/staff/start-break',
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
            console.log(workTimesId);
            $.ajax({
                type: 'POST',
                url: '/staff/stop-break',
                data: {
                    clientSubServiceId: clientSubServiceId,
                     workTimesId: workTimesId,
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    console.log(response);
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

<!-- Task Check before loggin out start -->
<script>
    function checkWorkTimeStatus() {
        $.ajax({
            url: '/staff/check-work-time-status', 
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

<!-- Assigned Work List -->

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
            var managerFirstName = $(this).data('manager-firstname');
            var rowData = $('#completedTasksTable').DataTable().row($(this).closest('tr')).data();
            var serviceName = rowData.servicename;
            var frequency = rowData.service_frequency;
            var deadline = rowData.service_deadline;

            $('#service_name1').val(serviceName);
            $('#manager_name1').val(managerFirstName);
            $('#service_frequency1').val(frequency);
            $('#service_deadline1').val(deadline);

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
                    return staff.id === subService.staff_id;
                });

                var staffName = staff ? staff.first_name : 'N/A';
                var duration = '';
                var firstWorkTime = subService.work_times[0];

                if (subService.sequence_status === 2) {
                    if (firstWorkTime) {
                        var durationInSeconds = firstWorkTime.duration;
                        var hours = Math.floor(durationInSeconds / 3600);
                        var minutes = Math.floor((durationInSeconds % 3600) / 60);
                        var seconds = durationInSeconds % 60;
                        duration = `<div>${hours}h ${minutes}m ${seconds}s</div>`;
                    }
                }

                var newRow = `
                    <tr>
                        <td>${subService.sub_service.name}</td>
                        <td>${moment(subService.deadline).format('DD.MM.YYYY')}</td>
                        <td>${staffName}</td>
                         <td>${subService.note ? subService.note : ''}</td>
                        <td>
                            ${  subService.sequence_status === 2 ? 'Work is completed' 
                                : subService.sequence_status === 1 ? 'Not Started' 
                                : subService.sequence_status === 0 ? 'Processing'
                                : 'N/A'
                             }
                         </td>
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

        $('#completed-service-cancelButton').click(function() {
            $('#completedTaskSection').hide();
        });
    });
</script>
<!-- Completed Work List -->

<!-- Note before logout start -->
<script>
    $('#saveNoteBtn').click(function() {
        var noteValue = $('#noteInput').val().trim();
        if (noteValue === '') {
            alert('Note field cannot be empty');
        } else {
            $('#noteForm').submit();
        }
    });
</script>
<!-- Note before logout end -->

@endsection
