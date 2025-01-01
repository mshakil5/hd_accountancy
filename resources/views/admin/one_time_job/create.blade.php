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
                  <th>Comment</th>
                </tr>
                </thead>
                <tbody>
                  @foreach ($data as $key => $data)
                  <tr class="
                      @if ($data->status == 2)
                          table-success
                      @elseif($data->status == 1)
                          table-warning
                      @elseif($data->status == 0)
                          table-info
                      @endif
                  ">
                    <td>{{ $key + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($data->created_at)->format('d-m-Y') }}</td>
                    <td>{{ $data->service->name }}</td>
                    <td>{{ $data->manager->first_name }} {{ $data->manager->last_name }}</td>
                    <td>{{ \Carbon\Carbon::parse($data->legal_deadline)->format('d-m-Y') }} </td>
                    <td>
                      @if ($data->status == 2)
                        <span>Completed</span>
                      @elseif($data->status == 1)
                        <span>Not Started Yet</span>
                      @elseif($data->status == 0)
                        <span>In Progress</span>
                      @endif
                    </td>
                    <td>
                      <button type="button" class="btn btn-secondary open-modal" data-toggle="modal" data-target="#messageModal" data-client-service-id="{{ $data->id }}">
                          <i class="fas fa-plus-circle"></i>
                      </button>
                  </td>
                  </tr>
                  @endforeach
                </tbody>
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
<!-- /.content -->

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
    $(function () {
      $("#example1").DataTable();
    });
</script>

<script>
  $(document).ready(function () {
      $("#addThisFormContainer").hide();
      $("#newBtn").click(function(){
          clearform();
          $("#newBtn").hide(100);
          $("#addThisFormContainer").show(300);

      });
      $("#FormCloseBtn").click(function(){
          $("#addThisFormContainer").hide(200);
          $("#newBtn").show(100);
          clearform();
      });
      $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
      var url = "{{URL::to('/admin/one-time-job')}}";
      $("#addBtn").click(function(){
          if($(this).val() == 'Create') {
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
                success: function (d) {
                    swal({
                        title: "Success!",
                        text: "Task assigned successfully",
                        icon: "success",
                        button: "OK",
                    });
                    window.setTimeout(function () {
                        location.reload();
                    }, 1000);
                },
                error: function (xhr, error, thrown) {
                    console.error(xhr.responseText);
                    let errorMessage = "An unknown error occurred.";
                    
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        errorMessage = Object.values(xhr.responseJSON.errors)[0][0];
                    }

                    swal({
                        title: "Error!",
                        text: errorMessage,
                        icon: "error",
                        button: "OK",
                    });
                }
            });
          }
      });

      function clearform(){
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
                  console.error('Error saving message :', error);
              }
          });
      });

  });
</script>
@endsection