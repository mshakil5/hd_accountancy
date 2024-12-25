@extends('admin.layouts.admin')

@section('content')

<section class="section">
  <div class="row">
    <div class="col-lg-12 px-0 shadow-sm border-theme border-2">
      <p class="p-2 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center">
        <i class='bi bi-journal-text fs-4 me-2'></i> One Time Job
      </p>

      <div class="row px-3">
        <div class="col-lg-12">
          <div class="card mt-4">
            <div class="card-body border-theme border-2">
              <form id="serviceForm">
                <div class="row">
                  <div class="col-md-12">
                    <div class="row mt-3">
                      <div class="col-3">
                        <div class="form-check">
                          <h5 class="mb-2">Choose Service <span class="text-danger">*</span></h5>
                          <select id="serviceDropdown" class="form-control mt-1 select2" style="width:100%">
                            <option value="" selected>Select Service</option>
                            @foreach($services as $service)
                            <option value="{{ $service->id }}">
                              {{ $service->name }}
                            </option>
                            @endforeach
                          </select>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </form>

              <div class="row mt-3">
                    <div class="col-lg-4 mx-auto text-center">
                        <button id="service-saveButton" class="btn btn-sm bg-theme text-light btn-outline-dark">Save</button>
                    </div>
              </div>

            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>


@endsection

@section('script')

<script>
  $(document).ready(function() {
    $('#serviceDropdown').change(function() {
      var serviceId = $(this).val();
      if (serviceId) {

        var exists = false;

        $('.subServiceDetails').each(function() {
          if ($(this).find('input[name="service_id"]').val() == serviceId) {
            exists = true;
            return false;
          }
        });
        if (exists) {
          alert('This service is already added.');
          return;
        }

        $.ajax({
          url: '/admin/getSubServices/' + serviceId,
          type: "GET",
          dataType: "json",
          success: function(data) {
            var subServiceDetailsTemplate = `
                  <div class="row mt-4 subServiceDetails">
                      <div class="col-12">
                      <h5 class="p-2 bg-theme text-white mb-0 text-capitalize">Services Details</h5>
                      <div class="border-theme p-3 border-1">
                          <div class="row mt-2">
                          <!-- Sub-service details -->
                          </div>
                          <table class="table mt-3">
                          <thead>
                              <tr>
                              <th>Sub Service</th>
                              <th>Deadline <span class="text-danger">*</span></th>
                              <th>Staff <span class="text-danger">*</span></th>
                              <th>Note</th>
                              <th style="text-align: center;">Action</th>
                              </tr>
                          </thead>
                          <tbody>
                              <!-- Sub-service rows  -->
                          </tbody>
                          </table>
                      </div>
                      </div>
                  </div>
                  `;

            $('#serviceForm').append(subServiceDetailsTemplate);

            var serviceName = $('#serviceDropdown option:selected').text();

            var serviceFields = `
                    <div class="row">
                        <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-3 text-center">
                            <h5 class="mb-3">Service</h5>
                            <p> <b>${serviceName}</b> </p>
                            <input type="hidden" name="service_id" value="${serviceId}">
                            <input type="hidden" name="client_service_id[]" value="">
                            </div>
                            <div class="col-md-4 text-center">
                                <h5 class="mb-3">Manager <span class="text-danger">*</span></h5>
                                <div class="form-check">
                                    <select class="form-control mt-2 managerDropdown" name="manager_id">
                                    <option value="">Select</option>
                                    @foreach($managers as $manager)
                                    <option value="{{ $manager->id }}">{{ $manager->first_name }}</option>
                                    @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <h5 class="mb-3">Target Deadline <span class="text-danger">*</span></h5>
                                <div class="form-check">
                                    <input type="date" class="form-control legalDeadline" id="legalDeadline" name="legalDeadline">
                                </div>
                            </div>
                            <div class="col-md-1 text-center">
                            <h5 class="mb-1">Action</h5>
                            <span class="removeSubServiceDetails" style="cursor: pointer; font-size: 24px; color: red;">&#10006;</span>
                            </div>
                        </div>
                        </div>
                    </div>
                    `;
            $('.subServiceDetails:last').find('.row:first').after(serviceFields);

            $('.subServiceDetails:last').find('tbody').empty();
            $.each(data, function(key, value) {
              var newRow = `
                        <tr>
                        <td>${value.name}</td>
                        <td><input type="date" id="deadline" name="deadline" class="form-control"></td>
                        <td>
                            <select class="form-control staffDropdown" id="selectedStaff" name="staff_id">
                            <option value="">Select Staff</option>
                            @foreach($staffs as $staff)
                            <option value="{{ $staff->id }}">{{ $staff->first_name }}</option>
                            @endforeach
                            </select>
                        </td>
                        <td><textarea name="note" id="note" rows="1" class="form-control" placeholder="Note for this task"></textarea></td>
                        <td style="text-align: center;">
                            <span class="removeSubServiceRow" style="cursor: pointer; font-size: 24px; color: red;">&#10006;</span>
                        </td>
                        <input type="hidden" class="sub-service-id" data-sub-service-id="${value.id}">
                        <input type="hidden" name="sub_service_id[]" value="${value.id}">
                        <input type="hidden" name="client_sub_service_id[]" value="">
                        </tr>
                    `;
              $('.subServiceDetails:last').find('tbody').append(newRow);
            });
          }
        });
      }
    });

    $(document).on('click', '.removeSubServiceRow', function() {
      $(this).closest('tr').remove();
    });

    $(document).on('click', '.removeSubServiceDetails', function() {
      $(this).closest('.subServiceDetails').remove();
    });

    $('#service-saveButton').click(function(e) {
        e.preventDefault();

        var services = [];
        $('.subServiceDetails').each(function() {
            var serviceId = $('#serviceDropdown').val();

            var managerId = $(this).find('.managerDropdown').val();
            var legal_deadline = $(this).find('.legalDeadline').val();
            var subServices = [];

            $(this).find('tbody tr').each(function() {
                var subServiceId = $(this).find('.sub-service-id').attr('data-sub-service-id');
                var deadline = $(this).find('input[type="date"]').val();
                var note = $(this).find('textarea').val();
                var staffId = $(this).find('.staffDropdown').val();

                subServices.push({
                    subServiceId: subServiceId,
                    deadline: deadline,
                    note: note,
                    staffId: staffId
                });
            });

            services.push({
                serviceId: serviceId,
                managerId: managerId,
                subServices: subServices,
                legal_deadline: legal_deadline,
            });
        });

        var data = {
            services: services
        };

        $.ajax({
            url: '/admin/one-time-job',
            type: 'POST',
            data: data,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                swal({
                    title: "Success!",
                    text: "Task assigned successfully",
                    icon: "success",
                    button: "OK",
                });
                setTimeout(function() {
                    location.reload();
                }, 2000);
            },
            error: function(xhr, status, error) {
              console.error(xhr.responseText);
                var errorMessage = "An error occurred. Please try again later.";
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    errorMessage = Object.values(xhr.responseJSON.errors)[0][0];
                }
                swal({
                    title: "Error",
                    text: errorMessage,
                    icon: "error",
                    button: "OK",
                });
            },
        });
    });

  });
</script>

@endsection