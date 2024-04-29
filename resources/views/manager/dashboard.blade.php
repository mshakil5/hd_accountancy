@extends('manager.layouts.manager')

@section('content')

<section class="section dashboard">
        <div class="row">

          <div class="col-lg-12">
              <div class="report-box border-theme sales-card p-4 mb-3 rounded-4 border-3" id="assignTaskSection" style="display: none;">
                  <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                       Change Work Status
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
                        <table id="serviceManagerTable" class="table cell-border table-striped" style="width:100%">
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
                <!-- Works assigned to a user and specified staff -->
                </div>
            </div>
        </div>
    </div>
</section>

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
        //     success: function(data) {
        //     console.log(data);
        // },
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
        var managerName = $(this).data('manager');
        var rowData = $('#serviceManagerTable').DataTable().row($(this).closest('tr')).data();
        var serviceName = rowData.servicename;
        var frequency = rowData.service_frequency;
        var deadline = rowData.service_deadline;

        $('#service_name').val(serviceName);
        $('#manager_name').val(managerName);
        $('#service_frequency').val(frequency);
        $('#service_deadline').val(deadline);

        $.ajax({
            url: '/manager/getClientSubServices/' + clientserviceId,
            type: "GET",
            dataType: "json",
            success: function(data) {
                // console.log(data);
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

        $.each(subServices, function(index, subService) {
            var statusText = '';
            var statusDropdown = '';
            // console.log(subService);

            var staff = staffs.find(function(staff) {
                return staff.id === subService.staff_id;
            });

            var staffName = staff ? staff.first_name : 'N/A';

            if (subService.sequence_status === 0) {
                statusDropdown = `
                    <select class="form-select change-service-status" data-sub-service-id="${subService.id}">
                        <option value="0" selected>Processing</option>
                        <option value="2">Completed</option>
                    </select>`;
            } else if (subService.sequence_status === 1) {
                statusText = 'Work isn\'t started yet';
            } else if (subService.sequence_status === 2) {
                statusText = 'Work is completed';
            }

            var newRow = `
                <tr>
                    <td>${subService.sub_service.name}</td>
                    <td>${subService.deadline}</td>
                    <td>${staffName}</td>
                    <td>${subService.note}</td>
                    <td>${statusText} ${statusDropdown}</td>
                </tr>
            `;
            subServiceTable.append(newRow);
        });

       $('#assignTaskSection').show();
    }

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
          // console.log('Status updated successfully:', response);
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
<!-- Assigned tasks list start -->

@endsection