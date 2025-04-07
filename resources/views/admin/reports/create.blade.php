@extends('admin.layouts.admin')

@section('content')

<section class="section">
  <div class="container-fluid">
      <div class="row">
          <div class="col-lg-12 px-0">
              <div class="px-3">
                  <div class="row my-2">
                      <div class="col-lg-4">
                          <label for="staff_id" class="form-label fw-bold">Report Name <span class="text-danger">*</span></label>
                          <input type="text" class="form-control rounded-1 border-1 border-theme bg-white" id="report_name">
                      </div>
                      <div class="col-lg-3">
                        <label for="report_base" class="form-label fw-bold">Report Base <span class="text-danger">*</span></label>
                        <select class="form-select rounded-1 border-1 border-theme bg-white" id="report_base">
                            <option value="employee">Employee</option>
                            <option value="client">Client</option>
                        </select>
                      </div>
                      <div class="col-lg-5">
                          <label for="base_name" class="form-label fw-bold">Base Name <span class="text-danger">*</span></label>
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
                        <label for="reservation" class="form-label fw-bold">Date Range <span class="text-danger">*</span></label>
                          <input type="text" class="form-control float-right bg-white" id="reservation">
                      </div>

                      <div class="col-lg-4">
                          <label for="start_date" class="form-label fw-bold">Compare With <span class="text-danger">*</span></label>
                          <select class="form-select rounded-1 border-1 border-theme bg-white" id="compare_with">
                              <option value="5">Compare With 5 Periods</option>
                              <option value="4">Compare With 4 Periods</option>
                              <option value="3">Compare With 3 Periods</option>
                              <option value="2">Compare With 2 Periods</option>
                          </select>
                      </div>

                      <div class="col-lg-3 d-flex align-items-end">
                          <label for="" class="form-label label-primary" style="visibility:hidden;">Action</label>
                          <button type="button" class="btn bg-theme text-light btn-outline-dark btn-generate-report" title="Generate Report">Generate Report</button>
                      </div>

                      <div class="col-lg-1">
                          <label for="" class="form-label label-primary" style="visibility:hidden;">Action</label>
                          <button type="button" class="btn btn-theme btn-outline-dark" id="btn-refresh"><i class="fas fa-sync-alt" title="Refresh"></i></button>
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
                <table class="table report-table">
                    <thead></thead>
                    <tbody></tbody>
                </table>
            </div>

            <div class="table-responsive mt-4" id="detailed_table_container" style="display: none;">
              <table class="table detailed-table">
                  <thead></thead>
                  <tbody></tbody>
                  <tfoot>
                    <tr>
                        <td class="text-center py-2 no-print" style="width: 100%;" colspan="6">
                            <button type="button" class="btn btn-primary btn-cancel-report ">Back</button>
                        </td>
                    </tr>
                  </tfoot>                
              </table>
            </div>

            <div class="table-responsive mt-4" id="hourly_detailed_table_container" style="display: none;">
              <table class="table hourly-detailed-table">
                  <thead></thead>
                  <tbody></tbody>
                  <tfoot>
                    <tr>
                        <td colspan="5" class="text-center no-print">
                            <button type="button" class="btn btn-primary hourly-cancel-report">Back</button>
                        </td>
                    </tr>
                </tfoot>
              </table>
            </div>
    
            <div class="d-flex justify-content-center gap-3 mt-5 no-print">
                <button onclick="window.print()" class="btn btn-primary btn-lg"><i class="fas fa-print"></i> Export to PDF</button>
                <button id="exportExcel" class="btn btn-success btn-lg"><i class="fas fa-file-excel"></i> Export to Excel</button>
            </div>
        </div>
      </div>
    
  </div>
</section>

<div id="loader" style="display: none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); z-index:9999;">
  <div class="spinner-border text-primary spinner-border-lg" role="status" style="width: 4rem; height: 4rem;"></div>
</div>

<style>
  @media print {
      body * {
          visibility: hidden;
      }
      #report_card, #report_card * {
          visibility: visible;
      }
      #report_card {
          position: absolute;
          left: 0;
          top: 0;
          -webkit-print-color-adjust: exact;
          print-color-adjust: exact;
          width: 100%;
          font-size: 12px;
      }
      .no-print {
          display: none !important;
      }
  }

  .period-header {
        font-style: italic;
        font-weight: 600;
    }

  #btn-refresh:hover {
      visibility: visible !important;
      opacity: 1 !important;
      display: inline-block !important;
  }

</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-daterangepicker/3.0.5/daterangepicker.min.css" integrity="sha512-rBi1cGvEdd3NmSAQhPWId5Nd6QxE8To4ADjM2a6n0BrqQdisZ/RPUlm0YycDzvNL1HHAh1nKZqI0kSbif+5upQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />

@endsection

@section('script')

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-daterangepicker/3.0.5/daterangepicker.min.js" integrity="sha512-mh+AjlD3nxImTUGisMpHXW03gE6F4WdQyvuFRkjecwuWLwD2yCijw4tKA3NsEFpA1C3neiKhGXPSIGSfCYPMlQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
  $(document).ready(function () {
      $("#exportExcel").on("click", function () {
          let visibleTable = $("#report_card").find("table:visible").get(0);
          if (!visibleTable) {
              toastr.error("No report table available for export.");
              return;
          }
  
          let wb = XLSX.utils.book_new();
          
          let visibleData = [];
          
          $(visibleTable).find('tr').each(function() {
              if ($(this).closest('tfoot').length === 0) {
                  let rowData = [];
                  $(this).find('th, td').each(function() {
                      if ($(this).is(':visible')) {
                          rowData.push($(this).text().trim());
                      }
                  });
                  if (rowData.length > 0) {
                      visibleData.push(rowData);
                  }
              }
          });
  
          let ws = XLSX.utils.aoa_to_sheet(visibleData);
          XLSX.utils.book_append_sheet(wb, ws, "Report");
  
          XLSX.writeFile(wb, "report.xlsx");
      });
  });
</script>

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

  $(document).on('click', '#btn-refresh', function() {
      location.reload();
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
        beforeSend: function () {
            $('#loader').show();
        },
        complete: function () {
            $('#loader').hide();
        },
        success: function (response) {

          // console.log(response);

          if(response.report_base == 'employee') {

            if (!response.work_times || !Array.isArray(response.work_times)) {
                console.error("Invalid response format:", response);
                toastr.error("Failed to generate report.");
                return;
            }

            $('#report_title').text(response.report_name);
            $('#report_base_name').text(response.report_base_name);
            $('#report_date_range').text(response.date_range);
            $('#report_card').show();

            let thead = `<tr>
                            <th>Ref ID</th>
                            <th>Client Name</th>`;
                            response.work_times.forEach(period => {
                                thead += `<th class="text-center period-header">${period.period}</th>`;
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
                            <td>
                                <a href="/admin/client/report/${client.client_id}" class="text-primary" target="_blank">${client.client_name}</a>
                            </td>`;

                response.work_times.forEach(period => {
                    let hoursWorked = client.periods[period.period] || "0.00";
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

          } 
          else if (response.report_base == 'client') {
            if (!response.work_times || !Array.isArray(response.work_times)) {
                console.error("Invalid response format:", response);
                toastr.error("Failed to generate report.");
                return;
            }

            $('#report_title').text(response.report_name);
            $('#report_base_name').text(response.report_base_name);
            $('#report_date_range').text(response.date_range);
            $('#report_card').show();

            let thead = `<tr>
                            <th>Staff ID</th>
                            <th>Staff Name</th>`;
            response.work_times.forEach(period => {
                thead += `<th class="text-center period-header">${period.period}</th>`;
            });
            thead += `</tr>`;
            $('.report-table thead').html(thead);

            let tbody = '';
            let totalHours = {};

            response.work_times.forEach(period => {
                totalHours[period.period] = 0;
            });

            let staff = {};

            response.work_times.forEach(period => {
                period.records.forEach(record => {
                    let key = record.staff_id;
                    if (!staff[key]) {
                        staff[key] = {
                            staff_id: record.staff_id,
                            staff_name: record.staff_name,
                            staff_id_number: record.staff_id_number,
                            periods: {}
                        };
                    }
                    let hoursWorked = (record.total_duration / 3600).toFixed(2);
                    staff[key].periods[period.period] = hoursWorked;

                    totalHours[period.period] += record.total_duration / 3600;
                });
            });

            Object.values(staff).forEach(staffMember => {
                tbody += `<tr>
                            <td>${staffMember.staff_id_number ?? ''}</td>
                            <td>${staffMember.staff_name}</td>`;

                response.work_times.forEach(period => {
                    let hoursWorked = staffMember.periods[period.period] || "0.00";
                    let clickableClass = parseFloat(hoursWorked) > 0 ? "clickable-client-hour text-primary" : "";
                    let cursorStyle = parseFloat(hoursWorked) > 0 ? "cursor: pointer;" : "";

                    tbody += `<td class="text-center ${clickableClass}" style="${cursorStyle}" 
                              data-period="${period.period}"
                              data-staff="${staffMember.staff_id}"
                              data-staff-name="${staffMember.staff_name}">
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
            $('#detailed_table_container').hide();
            $('#hourly_detailed_table_container').hide();
            $('#detail_report_title').hide();
          }
        },
        error: function (xhr, status, error) {
           toastr.error("Failed to fetch.");
           console.error("AJAX Error:", xhr.responseText);
        }
    });
  });

  $(document).on('click', '.clickable-client-hour', function() {
      let staffId = $(this).data('staff');
      let period = $(this).data('period');
      let staffName = $(this).data('staff-name');

      $.ajax({
          url: "{{ route('client.report.details') }}",
          type: "GET",
          data: {
              staff_id: staffId,
              date: period,
          },
          beforeSend: function () {
              $('#loader').show();
          },
          complete: function () {
              $('#loader').hide();
          },
          success: function(response) {
              // console.log(response);
              if (!response.details || response.details.length === 0) {
                  toastr.warning("No details found for this period.");
                  return;
              }

              $('#detail_report_title').text(`${staffName} - ${period}`).show();
              $('#report_title').hide();
              $('#report_table_container').hide();

              let thead = `<tr>
                              <th>${staffName}</th>
                              <th class="text-center period-header">Ref ID</th>
                              <th class="text-center period-header">Client Name</th>      
                              <th class="text-center period-header">Time</th>      
                              <th class="text-center period-header">Service Name</th>      
                              <th class="text-center period-header">Additional Work</th>
                          </tr>`;
              $('.detailed-table thead').html(thead);

              let tbody = '';
              let totalHours = 0;

              response.details.forEach(record => {
                    let hours = record.duration;
                    totalHours += parseFloat(hours);

                    let serviceName = '';
                    let additionalWork = '';

                    if (record.type == 2) {
                        additionalWork = record.service_name;
                        if (record.service_note) {
                            additionalWork += ` (${record.service_note})`;
                        }
                    } else {
                        serviceName = record.service_name;
                    }

                    tbody += `<tr>
                                  <td>${record.start_date}</td>
                                  <td class="text-center">${record.ref_id}</td>
                                  <td class="text-center">${record.client_name}</td>
                                  <td class="text-center">${hours}</td>
                                  <td class="text-center">${serviceName}</td>
                                  <td class="text-center">${additionalWork}</td>
                              </tr>`;
                });

              tbody += `<tr class="fw-bold">
                          <td colspan="3" class="text-end">Total</td>
                          <td class="text-center">${totalHours.toFixed(2)} hr</td>
                          <td colspan="2"></td>
                        </tr>`;

              $('.detailed-table tbody').html(tbody);
              $('#detailed_table_container').show();
          },
          error: function(xhr, status, error) {
              console.error(xhr.responseText);
              toastr.error("Failed to fetch details.");
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
        beforeSend: function () {
            $('#loader').show();
        },
        complete: function () {
            $('#loader').hide();
        },
        success: function(response) {
          console.log(response);
            if (!response.details || response.details.length === 0) {
                toastr.warning("No details found for this period.");
                return;
            }

            $('#detail_report_title').text(response.client_name + " - " + period).show();
            $('#report_title').hide();
            $('#report_table_container').hide();

            let thead = `<tr>
                            <th class="period-header">Date</th>
                            <th class="text-center period-header">Time</th>
                            <th class="text-center period-header">Service Name</th>      
                            <th class="text-center period-header">Additional Work</th>
                         </tr>`;
            $('.detailed-table thead').html(thead);

            let tbody = '';
            let totalHours = 0;

            response.details.forEach(record => {
                let hours = (record.duration / 3600).toFixed(2);
                totalHours += parseFloat(hours);

                let serviceName = '';
                let additionalWork = '';

                if (record.type == 2) {
                    additionalWork = record.service_name;
                } else {
                    serviceName = record.service_name;
                }

                tbody += `<tr>
                            <td>${record.date}</td>
                            <td class="text-center clickable-hour-detail text-primary" style="cursor: pointer;" data-client-id="${response.client_id}" data-date="${record.date}">
                                ${hours} hr
                            </td>
                            <td class="text-center">${serviceName}</td>
                            <td class="text-center">${additionalWork}</td>
                          </tr>`;
            });

            tbody += `<tr class="fw-bold">
                        <td colspan="1" class="text-end">Total</td>
                        <td class="text-center">${totalHours.toFixed(2)} hr</td>
                        <td colspan="2"></td>
                      </tr>`;

            $('.detailed-table tbody').html(tbody);
            $('#detailed_table_container').show();
        },
        error: function(xhr , status, error) {
            console.error(xhr.responseText);
            toastr.error("Failed to fetch details.");
        }
    });
  });

  $(document).on('click', '.clickable-hour-detail', function() {
      let clientId = $(this).data('client-id');
      let date = $(this).data('date');

      $.ajax({
          url: "{{ route('report.hourly.details') }}",
          type: "GET",
          data: {
              client_id: clientId,
              date: date,
          },
          beforeSend: function () {
              $('#loader').show();
          },
          complete: function () {
              $('#loader').hide();
          },
          success: function(response) {
              console.log(response);

              if (!response.details || response.details.length === 0) {
                  toastr.warning("No details found for this period.");
                  return;
              }

              $('#detailed_table_container').hide();

              let thead = `<tr>
                              <th>Staff ID</th>
                              <th>Staff Name</th>
                              <th class="text-center period-header">Time</th>
                              <th class="text-center period-header">Service Name</th>
                              <th class="text-center period-header">Additional Work</th>
                          </tr>`;
              $('.hourly-detailed-table thead').html(thead);

              let tbody = '';
              let totalHours = 0;

              response.details.forEach(record => {
                // console.log(record);
                  let hours = (record.duration / 3600).toFixed(2);
                  totalHours += parseFloat(hours);

                  let serviceName = '';
                  let additionalWork = '';

                  if (record.type == 2) {
                      additionalWork = record.service_name;
                      if (record.service_note) {
                          additionalWork += ` (${record.service_note})`;
                      }
                  } else {
                      serviceName = record.service_name;
                  }

                  tbody += `<tr>
                              <td>${record.staff_id ?? ''}</td>
                              <td>${record.staff_name}</td>
                              <td class="text-center">
                                  ${hours} hr
                              </td>
                              <td class="text-center">${serviceName}</td>
                              <td class="text-center">${additionalWork}</td>
                            </tr>`;
              });

              tbody += `<tr class="fw-bold">
                          <td colspan="2" class="text-end">Total</td>
                          <td class="text-center">${totalHours.toFixed(2)} hr</td>
                          <td colspan="2"></td>
                        </tr>`;

              $('.hourly-detailed-table tbody').html(tbody);
              $('#hourly_detailed_table_container').show();
          },
          error: function(xhr , status, error) {
              console.error(xhr.responseText);
              toastr.error("Failed to fetch details.");
          }
      });
  });

  $(document).on('click', '.hourly-cancel-report', function() {
    $('#detailed_table_container').show();
    $('#hourly_detailed_table_container').hide();
  });

  $(document).on('click', '.btn-cancel-report', function () {
    $('#detailed_table_container').hide();
    $('#report_title').show();
    $('#report_table_container').show();
    $('#detail_report_title').hide();
  });

</script>

@endsection