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
                              <option value="0">Select</option>
                              <option value="1">Staff</option>
                              <option value="2">Department</option>
                              <option value="3">Designation</option>
                          </select>
                      </div>
                      <div class="col-lg-2">
                          <label for="end_date" class="form-label fw-bold">Base Name</label>
                            <select class="form-select rounded-1 border-1 border-theme bg-white" id="base_name">
                              <option value="0">Select</option>
                              <option value="All">All</option>
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
                          <select class="form-select rounded-1 border-1 border-theme bg-white" id="report_base">
                              <option value="0">Select</option>
                              <option value="1">5 Periods</option>
                          </select>
                      </div>
                      <div class="col-lg-2">
                          <label for="" class="form-label label-primary" style="visibility:hidden;">Action</label>
                          <button type="button" class="btn bg-theme text-light btn-outline-dark">Generate Report</button>
                      </div>
                  </div>
              </div>
          </div>
      </div>

      <div class="card shadow-sm mt-4">
        <div class="card-body py-4">
            <h5 class="fw-bold">Employee Performance</h5>
            <p class="text-muted mb-3">All employees <br> For the month ended 30 January 2025</p>
    
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Ref ID</th>
                            <th>Client Names</th>
                            <th class="text-center">Jan 2025</th>
                            <th class="text-center">Dec 2024</th>
                            <th class="text-center">Nov 2024</th>
                            <th class="text-center">Oct 2024</th>
                            <th class="text-center">Sep 2024</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>LTD 137</td>
                            <td><a href="#" class="text-primary">JMF MAINTENANCE SERVICES LTD</a></td>
                            <td class="text-center">25 hr</td>
                            <td class="text-center">1000 hr</td>
                            <td class="text-center">100 hr</td>
                            <td class="text-center">150 hr</td>
                            <td class="text-center">150 hr</td>
                        </tr>
                        <tr>
                            <td>LTD 137</td>
                            <td><a href="#" class="text-primary">DECLAN STOREY LTD</a></td>
                            <td class="text-center">2125 hr</td>
                            <td class="text-center">5220 hr</td>
                            <td class="text-center">100 hr</td>
                            <td class="text-center">150 hr</td>
                            <td class="text-center">150 hr</td>
                        </tr>
                        <tr>
                            <td>PS 06</td>
                            <td><a href="#" class="text-primary">Travel With Kare</a></td>
                            <td class="text-center">2556 hr</td>
                            <td class="text-center">2500 hr</td>
                            <td class="text-center">100 hr</td>
                            <td class="text-center">150 hr</td>
                            <td class="text-center">150 hr</td>
                        </tr>
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="2">Total</td>
                            <td class="text-center">14525 hr</td>
                            <td class="text-center">12854 hr</td>
                            <td class="text-center">100 hr</td>
                            <td class="text-center">150 hr</td>
                            <td class="text-center">150 hr</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
    
            <div class="d-flex justify-content-center gap-3 mt-3">
                <button class="btn btn-primary">Save</button>
                <button class="btn btn-primary">Export As </button>
            </div>
        </div>
      </div>

  </div>
</section>

@endsection

@section('script')

<script>
  $('#reservation').daterangepicker();
</script>

@endsection