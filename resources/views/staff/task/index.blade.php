@extends('staff.layouts.staff')

@section('content')

<section id="breakSection" class="section dashboard">

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

    <!-- Assigned tasks table start-->
    <div class="col-lg-12 mb-3">
        <div class="report-box border-theme sales-card p-4 rounded-4 border-3 h-100 position-relative">
            <div class="card-body px-0">
                <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                    Task List
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
     <!-- Assigned tasks table end-->

</section>

@endsection

@section('script')

<script>
    var authId = @json(auth()->id());
</script>

<!-- Assigned Work List Start-->
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

                var staffName = staff ? (staff.first_name + ' ' + (staff.last_name || '')).trim() : 'N/A';
                var isAuthUserStaff = authUserId === subService.staff_id;
                var hasWorkTimes = subService.work_times && subService.work_times.length > 0;

                if (subService.sequence_status === 0) {
                    statusText = 'Processing';
                } else if (subService.sequence_status === 1) {
                    statusText = "Work isn't started yet";
                } else if (subService.sequence_status === 2) {
                    statusText = 'Work is completed';
                }

                var duration = '';

                var totalDurationInSeconds = subService.work_times.filter(workTime => workTime.is_break === 0)
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
                        <td>${moment(subService.deadline).format('DD.MM.YYYY')}</td>
                        <td>${staffName}</td>
                        <td>${subService.note ? subService.note : ''}</td>
                        <td>${statusText}</td>
                        <td>
                            <button type="button" class="btn btn-secondary open-modal" data-toggle="modal" data-target="#messageModal" data-staff-id="${subService.staff_id}" data-client-sub-service-id="${subService.id}">
                                <i class="fas fa-plus-circle"></i>
                            </button>
                        </td>
                        <td>
                            <span class="badge bg-success">${duration}</span>
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

  });
</script>
<!-- Assigned Work List End-->

@endsection