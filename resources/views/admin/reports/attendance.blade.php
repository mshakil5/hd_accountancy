@extends('admin.layouts.admin')

@section('content')
<section class="section">
  <div class="container-fluid">

    <h4 class="fw-bold mb-4">Attendance Report</h4>

    <div class="card border-theme border-1 shadow-sm mb-4">
      <div class="card-body">
        <h6 class="fw-bold mb-3">Report Option</h6>
        <div class="row g-3 align-items-end">
          <div class="col-lg-3">
            <label class="form-label fw-semibold">Date</label>
            <input type="text" class="form-control bg-white" id="att_date_range">
          </div>
          <div class="col-lg-3">
            <label class="form-label fw-semibold">Filter by</label>
            <select class="form-select select2 bg-white" id="att_employee_id">
              <option value="">-- Select Employee --</option>
              @foreach ($employees as $emp)
                <option value="{{ $emp->id }}">{{ $emp->first_name }} {{ $emp->last_name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-lg-auto d-flex gap-2 no-print">
            <button class="btn bg-theme text-light btn-outline-dark" id="btn_generate_attendance">Generate Report</button>
            <button class="btn btn-dark" onclick="window.print()"><i class="fas fa-print"></i> Export PDF</button>
            <button class="btn btn-success" id="att_exportExcel"><i class="fas fa-file-excel"></i> Export Excel</button>
            <button class="btn btn-secondary" onclick="location.reload()"><i class="fas fa-sync-alt"></i></button>
          </div>
        </div>
      </div>
    </div>

    <div id="att_report_section" style="display:none;">

      {{-- Summary Bar --}}
      <div class="d-flex justify-content-between align-items-center px-4 py-3 mb-3 rounded-1" style="background:#e9ecef;">
        <span class="fw-bold">Total Employee - <span id="sum_employees">0</span></span>
        <span class="fw-bold">Total Active hours - <span id="sum_active">0</span></span>
        <span class="fw-bold">Total Worked hours - <span id="sum_worked">0</span></span>
        <span class="fw-bold">Break Time - <span id="sum_break">0</span></span>
      </div>

      {{-- Table --}}
      <div class="card border-theme border-1 shadow-sm">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle" id="att_table">
              <thead class="table-light"></thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>

    </div>

  </div>
</section>

<div id="loader" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); z-index:9999;">
  <div class="spinner-border text-primary" style="width:4rem;height:4rem;" role="status"></div>
</div>

<style>
@media print {
  body * { visibility: hidden; }
  #att_report_section, #att_report_section * { visibility: visible; }
  #att_report_section { position: absolute; left: 0; top: 0; width: 100%; font-size: 10px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  .no-print { display: none !important; }
}
</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-daterangepicker/3.0.5/daterangepicker.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
@endsection

@section('script')
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-daterangepicker/3.0.5/daterangepicker.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
$(document).ready(function () {

  $('.select2').select2();

  $('#att_date_range').daterangepicker({
    locale: { format: 'DD/MM/YYYY' },
    startDate: moment().startOf('month'),
    endDate: moment(),
  });

  $('#btn_generate_attendance').on('click', function () {
    let date_range  = $('#att_date_range').val();
    let employee_id = $('#att_employee_id').val();

    if (!date_range)  { toastr.error('Please select a date range.'); return; }
    if (!employee_id) { toastr.error('Please select an employee.'); return; }

    $.ajax({
      url: "{{ route('attendance.report.generate') }}",
      type: 'GET',
      data: { date_range, employee_id },
      beforeSend: function () { $('#loader').show(); },
      complete:   function () { $('#loader').hide(); },
      success: function (response) {
        let { data, summary, employee_name } = response;

        $('#sum_employees').text(summary.total_employees);
        $('#sum_active').text(summary.total_active);
        $('#sum_worked').text(summary.total_worked);
        $('#sum_break').text(summary.total_break);

        let maxBreaks = 0;
        data.forEach(row => { if (row.breaks.length > maxBreaks) maxBreaks = row.breaks.length; });

        let theadHtml = `<tr>
          <th>Date</th>
          <th>Day</th>
          <th>Employee Name</th>
          <th class="text-center">Active hour</th>
          <th class="text-center">Worked Hours</th>
          <th class="text-center">Break Hours</th>
          <th class="text-center">Entry</th>
          <th class="text-center">Exit</th>`;

        for (let i = 0; i < maxBreaks; i++) {
          theadHtml += `<th class="text-center">Break ${i+1}</th>
                        <th class="text-center">Break ${i+1} Start</th>
                        <th class="text-center">Break ${i+1} End</th>`;
        }
        theadHtml += `</tr>`;
        $('#att_table thead').html(theadHtml);

        let tbodyHtml = '';
        data.forEach(row => {
          let isAbsent = row.active_hours === '-';
          tbodyHtml += `<tr ${isAbsent ? 'style="color:#aaa;"' : ''}>
            <td>${row.date}</td>
            <td>${row.day}</td>
            <td>${row.employee_name}</td>
            <td class="text-center">${row.active_hours}</td>
            <td class="text-center">${row.worked_hours}</td>
            <td class="text-center">${row.break_hours}</td>
            <td class="text-center">${row.entry}</td>
            <td class="text-center">${row.exit}</td>`;

          for (let i = 0; i < maxBreaks; i++) {
            if (row.breaks[i]) {
              tbodyHtml += `<td class="text-center">${row.breaks[i].duration}</td>
                            <td class="text-center">${row.breaks[i].start}</td>
                            <td class="text-center">${row.breaks[i].end}</td>`;
            } else {
              tbodyHtml += `<td class="text-center">-</td><td class="text-center">-</td><td class="text-center">-</td>`;
            }
          }
          tbodyHtml += `</tr>`;
        });

        $('#att_table tbody').html(tbodyHtml);
        $('#att_report_section').show();
      },
      error: function (xhr) {
        toastr.error('Failed to generate report.');
        console.error(xhr.responseText);
      }
    });
  });

  $('#att_exportExcel').on('click', function () {
    let visibleData = [];
    $('#att_table').find('tr').each(function () {
      let rowData = [];
      $(this).find('th, td').each(function () { rowData.push($(this).text().trim()); });
      if (rowData.length > 0) visibleData.push(rowData);
    });
    let wb = XLSX.utils.book_new();
    let ws = XLSX.utils.aoa_to_sheet(visibleData);
    XLSX.utils.book_append_sheet(wb, ws, 'Attendance');
    XLSX.writeFile(wb, 'attendance_report.xlsx');
  });

});
</script>
@endsection