@extends('admin.layouts.admin')

@section('content')

<section class="section">
  <div class="container-fluid">
      <div class="row">
          <div class="col-lg-12 px-0">
              <div class="px-3">
                  <div class="row my-2">
                      <div class="col-lg-4">
                          <label for="staff_id" class="form-label fw-bold">Report Name </label>
                          <input type="text" class="form-control rounded-1 border-1 border-theme bg-white" id="report_name">
                      </div>
                      <div class="col-lg-2">
                        <label for="report_base" class="form-label fw-bold">Report Base</label>
                        <select class="form-select rounded-1 border-1 border-theme bg-white" id="report_base">
                            <option value="employee">Employee</option>
                            <option value="client">Client</option>
                        </select>
                      </div>
                    
                      <div class="col-lg-4">
                          <label for="base_name" class="form-label fw-bold">Base Name</label>
                          <select class="form-select rounded-1 border-1 border-theme bg-white select2" id="base_name">
                              <option value="All">All</option>
                              @foreach ($employees as $employee)
                                  <option value="{{ $employee->id }}" data-type="employee">
                                    {{ $employee->id_number }} - {{ $employee->first_name }} {{ $employee->last_name }}
                                  </option>
                              @endforeach
                          </select>
                      </div>
                  </div>

                  <div class="row my-2">
                    <div class="col-lg-4">
                      <label for="reservation" class="form-label fw-bold">Date Range</label>
                        <input type="text" class="form-control float-right bg-white" id="reservation">
                    </div>

                      <div class="col-lg-4">
                          <label for="start_date" class="form-label fw-bold">Compare With</label>
                          <select class="form-select rounded-1 border-1 border-theme bg-white" id="compare_with">
                              <option value="5">Compare With 5 Periods</option>
                              <option value="4">Compare With 4 Periods</option>
                              <option value="3">Compare With 3 Periods</option>
                              <option value="2">Compare With 2 Periods</option>
                          </select>
                      </div>
                      <div class="col-lg-2">
                          <label for="" class="form-label label-primary" style="visibility:hidden;">Action</label>
                          <button type="button" class="btn bg-theme text-light btn-outline-dark btn-generate-report">Generate Report</button>
                      </div>
                  </div>
              </div>
          </div>
      </div>

      <div class="card shadow-sm mt-4 border-1 border-theme" id="report_card" style="display: none;">
        <div class="card-body py-4">
            <h2 class="fw-bold mb-2" id="report_title"></h2>
            <h2 class="fw-bold mb-2" id="detail_report_title" style="display: none;"></h2>
            <p class="text-muted my-2" id="report_base_name"></p>
            <p class="text-muted mb-4" id="report_date_range"></p>
    
            <div class="table-responsive" id="report_table_container">
                <table class="table table-bordered report-table">
                    <thead class="table-light"></thead>
                    <tbody></tbody>
                </table>
            </div>

            <div class="table-responsive mt-4" id="detailed_table_container" style="display: none;">
              <table class="table table-bordered detailed-table">
                  <thead class="table-light"></thead>
                  <tbody></tbody>
                  <tfoot>
                    <tr>
                        <td colspan="3" class="text-center">
                            <button type="button" class="btn btn-danger btn-cancel-report">Back</button>
                        </td>
                    </tr>
                </tfoot>
              </table>
            </div>
    
            <div class="d-flex justify-content-center gap-3 mt-3">
                <button class="btn btn-primary">Export</button>
            </div>
        </div>
      </div>
    
  </div>
</section>

@endsection

@section('script')

<script>
  $(document).ready(function() {
      let employees = @json($employees);
      let clients = @json($clients);

      $('#report_base').change(function() {
          let selectedType = $(this).val();
          let baseNameSelect = $('#base_name');
          
          baseNameSelect.empty().append('<option value="All">All</option>');

          if (selectedType === 'employee') {
              employees.forEach(employee => {
                  baseNameSelect.append(`<option value="${employee.id}">${employee.id_number ? employee.id_number + ' - ' : ''}${employee.first_name} ${employee.last_name}</option>`);
              });
          } else {
              clients.forEach(client => {
                  baseNameSelect.append(`<option value="${client.id}">${client.refid} - ${client.name}</option>`);
              });
          }

          baseNameSelect.trigger('change');
      });

      $('.select2').select2();
  });

  $('#reservation').daterangepicker({
    locale: {
        format: 'DD/MM/YYYY'
    }
  });

  $(document).on('click', '.btn-generate-report', function () {
    let report_name = $('#report_name').val();
    let report_base = $('#report_base').val();
    let base_name = $('#base_name').val();
    let date_range = $('#reservation').val();
    let compare_with = $('#compare_with').val();

    if (!report_name || !report_base || !base_name || !date_range || !compare_with) {
        toastr.error("Please fill all fields.");
        return;
    }

    $.ajax({
        url: "{{ route('report.generate') }}",
        type: "GET",
        data: {
            report_name: report_name,
            report_base: report_base,
            base_name: base_name,
            date_range: date_range,
            compare_with: compare_with,
        },
        success: function (response) {
            if (!response.work_times || !Array.isArray(response.work_times)) {
                console.error("Invalid response format:", response);
                toastr.error("Failed to generate report.");
                return;
            }

            $('#report_title').text(response.report_name);
            $('#report_base_name').text(response.report_base);
            $('#report_date_range').text(response.date_range);
            $('#report_card').show();

            let thead = `<tr>
                            <th>Ref ID</th>
                            <th>Client Name</th>`;
            response.work_times.forEach(period => {
                thead += `<th class="text-center">${period.period}</th>`;
            });
            thead += `</tr>`;
            $('.report-table thead').html(thead);

            let tbody = '';
            let totalHours = {};

            response.work_times.forEach(period => {
                totalHours[period.period] = 0;
            });

            let clients = {};

            response.work_times.forEach(period => {
                period.records.forEach(record => {
                    let key = record.client_id;
                    if (!clients[key]) {
                        clients[key] = {
                            client_id: record.client_id,
                            client_name: record.client_name,
                            refid: record.refid,
                            periods: {}
                        };
                    }
                    let hoursWorked = (record.total_duration / 3600).toFixed(2);
                    clients[key].periods[period.period] = hoursWorked;

                    totalHours[period.period] += record.total_duration / 3600;
                });
            });

            Object.values(clients).forEach(client => {
                tbody += `<tr>
                            <td>${client.refid ?? 'N/A'}</td>
                            <td><a href="/admin/client/report/${client.client_id}" class="text-primary">${client.client_name}</a></td>`;

                response.work_times.forEach(period => {
                    let hoursWorked = client.periods[period.period] || "0";
                    let clickableClass = parseFloat(hoursWorked) > 0 ? "clickable-hour text-primary" : "";
                    let cursorStyle = parseFloat(hoursWorked) > 0 ? "cursor: pointer;" : "";

                    tbody += `<td class="text-center ${clickableClass}" style="${cursorStyle}" 
                               data-period="${period.period}" data-client="${client.client_id}">
                               ${hoursWorked} hr
                             </td>`;
                });

                tbody += `</tr>`;
            });

            $('.report-table tbody').html(tbody);

            let totalRow = `<tr class="fw-bold">
                                <td colspan="2" class="text-end">Total</td>`;
            response.work_times.forEach(period => {
                totalRow += `<td class="text-center">${totalHours[period.period].toFixed(2)} hr</td>`;
            });
            totalRow += `</tr>`;

            $('.report-table tbody').append(totalRow);
        },
        error: function (xhr) {
            console.error("AJAX Error:", xhr.responseText);
        }
    });
  });

  $(document).on('click', '.clickable-hour', function() {
    let clientId = $(this).data('client');
    let period = $(this).data('period');

    $.ajax({
        url: "{{ route('report.details') }}",
        type: "GET",
        data: {
            client_id: clientId,
            period: period,
        },
        success: function(response) {
            if (!response.details || response.details.length === 0) {
                toastr.warning("No details found for this period.");
                return;
            }

            $('#detail_report_title').text(response.client_name + " - " + period).show();
            $('#report_title').hide();
            $('#report_table_container').hide();

            let thead = `<tr>
                            <th>Date</th>
                            <th class="text-center">Time (hr)</th>
                            <th class="text-center">Service Name</th>
                         </tr>`;
            $('.detailed-table thead').html(thead);

            let tbody = '';
            let totalHours = 0;

            response.details.forEach(record => {
                let hours = (record.duration / 3600).toFixed(2);
                totalHours += parseFloat(hours);

                tbody += `<tr>
                            <td>${record.date}</td>
                            <td class="text-center">${hours} hr</td>
                            <td class="text-center">${record.service_name ?? 'N/A'}</td>
                          </tr>`;
            });

            tbody += `<tr class="fw-bold">
                        <td colspan="1" class="text-end">Total</td>
                        <td class="text-center">${totalHours.toFixed(2)} hr</td>
                        <td></td>
                      </tr>`;

            $('.detailed-table tbody').html(tbody);
            $('#detailed_table_container').show();
        },
        error: function(xhr) {
            console.error("AJAX Error:", xhr.responseText);
            toastr.error("Failed to fetch details.");
        }
    });
  });

  $(document).on('click', '.btn-cancel-report', function () {
    $('#detailed_table_container').hide();
    $('#report_title').show();
    $('#report_table_container').show();
    $('#detail_report_title').hide();
  });

</script>

@endsection