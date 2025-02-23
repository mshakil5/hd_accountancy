@extends('admin.layouts.admin')

@section('content')

<section class="section">
  <div class="container-fluid">
      <div class="row">
          <div class="col-lg-12 px-0">
              <div class="px-3">
                  <div class="row my-2">
                      <div class="col-lg-4">
                          <label for="staff_id" class="form-label fw-bold">Report Name</label>
                          <input type="text" class="form-control rounded-1 border-1 border-theme bg-white" id="report_name">
                      </div>
                      <div class="col-lg-2">
                          <label for="start_date" class="form-label fw-bold">Report Base</label>
                          <select class="form-select rounded-1 border-1 border-theme bg-white" id="report_base">
                              <option value="emplpyee">Employee</option>
                          </select>
                      </div>
                      <div class="col-lg-2">
                          <label for="end_date" class="form-label fw-bold">Base Name</label>
                            <select class="form-select rounded-1 border-1 border-theme bg-white select2" id="base_name">
                              <option value="All">All</option>
                              @foreach ($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->name }}</option>
                              @endforeach
                          </select>
                      </div>
                  </div>

                  <div class="row my-2">
                    <div class="col-lg-4">
                      <label for="reservation" class="form-label fw-bold">Date Range</label>
                        <input type="text" class="form-control float-right bg-white" id="reservation">
                    </div>

                      <div class="col-lg-2">
                          <label for="start_date" class="form-label fw-bold">Compare With</label>
                          <select class="form-select rounded-1 border-1 border-theme bg-white" id="compare_with">
                              <option value="5">5 Periods</option>
                              <option value="4">4 Periods</option>
                              <option value="3">3 Periods</option>
                              <option value="2">2 Periods</option>
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
            <h5 class="fw-bold" id="report_title"></h5>
            <p class="text-muted mb-3" id="report_date_range"></p>
    
            <div class="table-responsive">
                <table class="table table-bordered report-table">
                    <thead class="table-light"></thead>
                    <tbody></tbody>
                </table>
            </div>
    
            <div class="d-flex justify-content-center gap-3 mt-3">
                <button class="btn btn-primary">Save</button>
                <button class="btn btn-primary">Export As</button>
            </div>
        </div>
      </div>
    
  </div>
</section>

@endsection

@section('script')

<script>
  $('#reservation').daterangepicker({
    locale: {
        format: 'DD/MM/YYYY'
    }
  });

  $(document).on('click', '.btn-generate-report', function () {
    let report_name = $('#report_name').val();
    let base_name = $('#base_name').val();
    let date_range = $('#reservation').val();
    let compare_with = $('#compare_with').val();

    $.ajax({
        url: "{{ route('report.generate') }}",
        type: "GET",
        data: {
            report_name: report_name,
            base_name: base_name,
            date_range: date_range,
            compare_with: compare_with,
        },
        success: function (response) {
            // console.log(response);

            if (!response.periods || !Array.isArray(response.periods)) {
                console.error("Invalid periods data:", response.periods);
                return;
            }

            $('#report_title').text(response.report_name);
            $('#report_date_range').text(date_range);
            $('#report_card').show();

            let thead = `<tr>
                            <th>Ref ID</th>
                            <th>Client Names</th>`;
            response.periods.forEach(period => {
                thead += `<th class="text-center">${period}</th>`;
            });
            thead += `</tr>`;
            $('.report-table thead').html(thead);

            let tbody = '';
            let totalHours = {}; 

            response.periods.forEach(period => {
                totalHours[period] = 0;
            });

            response.report_data.forEach(row => {
                tbody += `<tr>
                            <td>${row.client_ref}</td>
                            <td><a href="#" class="text-primary">${row.client_name}</a></td>`;

                response.periods.forEach(period => {
                    let hoursWorked = row.hours[period] || "0 hr";
                    tbody += `<td class="text-center">${hoursWorked}</td>`;
                    
                    totalHours[period] += parseFloat(hoursWorked);
                });

                tbody += `</tr>`;
            });

            $('.report-table tbody').html(tbody);

            let totalRow = `<tr class="fw-bold">
                                <td colspan="2" class="text-end">Total</td>`;
            response.periods.forEach(period => {
                totalRow += `<td class="text-center">${totalHours[period].toFixed(2)} hr</td>`;
            });
            totalRow += `</tr>`;

            $('.report-table tbody').append(totalRow);
        },
        error: function (xhr) {
            console.error("AJAX Error:", xhr.responseText);
        }
    });
  });
</script>

@endsection