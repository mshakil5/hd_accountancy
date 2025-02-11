@extends('admin.layouts.admin')

@section('content')

<!-- Main content -->
<section class="content mt-3" id="newBtnSection">
    <div class="container-fluid">
        <div class="row">
            <div class="col-2">
                <button type="button" class="btn btn-secondary my-3" id="newBtn">Create new</button>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->



<!-- Main content -->
<section class="content" id="addThisFormContainer">
    <div class="container-fluid">
        <div class="row justify-content-md-center">
            <!-- right column -->
            <div class="col-md-6">
                <!-- general form elements disabled -->
                <div class="card card-secondary">
                    <div class="card-header">
                        <h3 class="card-title">Add new one time job</h3>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <div class="ermsg">
                        </div>
                        <form id="createThisForm">
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label>Task <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="task" name="task">
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="manager_id">Assign To <span class="text-danger">*</span></label>
                                        <select class="form-control select2" id="manager_id" name="manager_id">
                                            <option value="">Select a manager or staff</option>
                                            @foreach ($managerAndStaffs as $staff)
                                            <option value="{{ $staff->id }}">{{ $staff->first_name }} {{ $staff->last_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label>Deadline</label>
                                        <input type="date" class="form-control" id="legal_deadline" name="legal_deadline">
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <!-- /.card-body -->
                    <div class="card-footer">
                        <button type="submit" id="addBtn" class="btn btn-secondary" value="Create">Create</button>
                        <button type="submit" id="FormCloseBtn" class="btn btn-default">Cancel</button>
                    </div>
                    <!-- /.card-footer -->
                    <!-- /.card-body -->
                </div>
            </div>
            <!--/.col (right) -->
        </div>
    </div><!-- /.container-fluid -->
</section>
<!-- /.content -->


<!-- Main content -->
<section class="content" id="contentContainer">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <!-- /.card -->

                <div class="card card-secondary">
                    <div class="card-header">
                        <h3 class="card-title">One Time Jobs</h3>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <table id="example1" class="table cell-border table-striped">
                            <thead>
                                <tr>
                                    <th>Sl</th>
                                    <th>Assigned Date</th>
                                    <th>Task</th>
                                    <th>Assigned To</th>
                                    <th>Deadline</th>
                                    <th>Status</th>
                                    <th>Log</th>
                                    <th>Comment</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->
        </div>
        <!-- /.row -->
    </div>
    <!-- /.container-fluid -->
</section>

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
                                <input type="hidden" id="hiddenClientServiceId" />
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


@endsection
@section('script')

<script>
    const table = $('#example1').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/admin/one-time-job/data',
            type: 'GET',
            dataSrc: 'data',
            error: function (xhr, error, thrown) {
                console.error(xhr.responseText);
            }
        },
        columns: [
            {
                data: null,
                render: function (data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            {
                data: 'created_at',
                name: 'created_at',
                render: function (data) {
                    return moment(data).format('DD-MM-YYYY');
                }
            },
            {
                data: 'service.name',
                name: 'service.name'
            },
            {
                data: 'manager.first_name',
                name: 'manager.first_name'
            },
            {
                data: 'legal_deadline',
                name: 'legal_deadline',
                render: function(data, type, row) {
                    var legalDeadlineDate = moment(data, 'DD-MM-YYYY');
                    if (row.status != 2 && legalDeadlineDate.isBefore(moment(), 'day')) { 
                        return '<span style="color: red;">' + data + '</span>';
                    }
                    return data;
                }
            },
            {
                data: 'status',
                name: 'status',
                render: function (data) {
                    const statusMap = {
                        1: 'Not Started',
                        0: 'Processing',
                        2: 'Completed'
                    };

                    return statusMap[data] ?? 'Unknown Status';
                }
            },
            {
                data: 'activity',
                name: 'activity',
                render: function (data, type, row) {
                    return `
                        <a href="{{ url('admin/one-time-job-activity') }}/${row.id}" class="btn btn-primary">
                            <i class="fas fa-book"></i>
                        </a>
                    `;
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
                        <button type="button" class="btn btn-secondary open-modal" data-toggle="modal" data-target="#messageModal" data-client-service-id="${row.id}">
                            <i class="fas fa-plus-circle"></i>${newMessageIcon}
                        </button>
                    `;
                }
            }
        ]
    });

    $('#messageModal').on('hidden.bs.modal', function () {
        table.ajax.reload();
    });

    setInterval(function () {
        table.ajax.reload();
    }, 30000);

</script>

<script>
    $(document).ready(function() {
        $("#addThisFormContainer").hide();
        $("#newBtn").click(function() {
            clearform();
            $("#newBtn").hide(100);
            $("#addThisFormContainer").show(300);

        });
        $("#FormCloseBtn").click(function() {
            $("#addThisFormContainer").hide(200);
            $("#newBtn").show(100);
            clearform();
        });
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        var url = "{{URL::to('/admin/one-time-job')}}";
        $("#addBtn").click(function() {
            if ($(this).val() == 'Create') {
                var form_data = new FormData();
                form_data.append("task", $("#task").val());
                form_data.append("manager_id", $("#manager_id").val());
                form_data.append("legal_deadline", $("#legal_deadline").val());
                $.ajax({
                    url: url,
                    method: "POST",
                    contentType: false,
                    processData: false,
                    data: form_data,
                    success: function(d) {
                        toastr.success("Task assigned successfully", "Success");

                        clearform();
                        $("#addThisFormContainer").hide(100);
                        $("#newBtn").show(100);
                        $('#example1').DataTable().ajax.reload();
                        
                    },
                    error: function(xhr, error, thrown) {
                        console.error(xhr.responseText);
                        let errorMessage = "An unknown error occurred.";

                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            errorMessage = Object.values(xhr.responseJSON.errors)[0][0];
                        }

                        toastr.error(errorMessage, "Error");
                    }
                });
            }
        });

        function clearform() {
            $('#createThisForm')[0].reset();
            $("#addBtn").val('Create');
        }

        $(document).on('click', '.open-modal', function() {
            var clientServiceId = $(this).data('client-service-id');
            $('#hiddenClientServiceId').val(clientServiceId);
            populateMessage(clientServiceId);
            $('#messageModal').modal('show');
        });

        function populateMessage(clientServiceId) {
            $.ajax({
                url: '/admin/getServiceComment/' + clientServiceId,
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
                    console.error(xhr.responseText);
                    console.error('Error fetching previous messages:', error, thrown);
                }
            });
        }

        $('#saveMessage').click(function() {
            var message = $('#service-message').val();
            var clientServiceId = $('#hiddenClientServiceId').val();

            $.ajax({
                url: '/admin/store-comment',
                type: "POST",
                data: {
                    message: message,
                    client_service_id: clientServiceId,
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    $('#service-message').val('');
                    populateMessage(clientServiceId);
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                    console.error('Error saving message :', error);
                }
            });
        });

    });
</script>
@endsection