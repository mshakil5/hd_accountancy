@extends('admin.layouts.admin')

@section('content')

<!-- Main content -->
<section class="content" id="contentContainer">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <!-- /.card -->
          <div class="card card-secondary border-theme border-2">
            <div class="card-header">
              <h3 class="card-title">All Data</h3>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
            <div class="table-responsive">
              <table id="example1" class="table cell-border table-striped">
                <thead>
                <tr>
                  <th style="text-align: center">Sl</th>
                  <th style="text-align: center">Date</th>
                  <th style="text-align: center">Name</th>
                  <th style="text-align: center">Contact</th>
                  <th style="text-align: center">Company</th>
                  <th style="text-align: center">Business Type</th>
                  <th style="text-align: center">Action</th>
                </tr>
                </thead>
                <tbody>
                  @foreach ($data as $key => $data)
                  <tr>
                    <td style="text-align: center">{{ $key + 1 }}</td>
                    <td style="text-align: center">{{ date('d-m-Y', strtotime($data->created_at)) }}</td>
                    <td style="text-align: center">{{$data->name}}</td>
                    <td style="text-align: center">{{$data->email}}, {{$data->phone}}</td>
                    <td style="text-align: center">{{$data->company_name}}</td>
                    <td style="text-align: center">{{$data->business_type}}</td>
                    <td style="text-align: center">
                      <a class="btn btn-link viewQuotation" 
                        data-id="{{ $data->id }}" 
                        data-name="{{ $data->name }}"
                        data-email="{{ $data->email }}"
                        data-company="{{ $data->company_name }}"
                        data-phone="{{ $data->phone }}"
                        data-business_type="{{ $data->business_type }}"
                        data-turnover="{{ $data->turnover }}"
                        data-vat_returns="{{ $data->vat_returns }}"
                        data-payroll="{{ $data->payroll }}"
                        data-bookkeeping="{{ $data->bookkeeping }}"
                        data-bookkeeping_software="{{ $data->bookkeeping_software }}"
                        data-management_account="{{ $data->management_account }}"
                        data-bank_accounts="{{ $data->bank_accounts }}"
                        data-bs-toggle="modal" data-bs-target="#viewModal">
                          <i class="fas fa-eye" style="color: blue; font-size: 20px;"></i>
                      </a>
                      <a class="btn btn-link" id="deleteBtn" rid="{{$data->id}}"><i class="fas fa-trash" style="color: red; font-size: 20px;"></i></a>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
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

<!-- Modal -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="viewModalLabel">Quotation Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-6">
            <p><strong>Name:</strong> <span id="modal-name"></span></p>
            <p><strong>Email:</strong> <span id="modal-email"></span></p>
            <p><strong>Company:</strong> <span id="modal-company"></span></p>
            <p><strong>Phone:</strong> <span id="modal-phone"></span></p>
          </div>
          <div class="col-6">
            <p><strong>Business Type:</strong> <span id="modal-business_type"></span></p>
            <p><strong>Turnover:</strong> <span id="modal-turnover"></span></p>
            <p><strong>VAT Returns:</strong> <span id="modal-vat_returns"></span></p>
            <p><strong>Payroll:</strong> <span id="modal-payroll"></span></p>
          </div>
        </div>
        <div class="row">
          <div class="col-6">
            <p><strong>Bookkeeping:</strong> <span id="modal-bookkeeping"></span></p>
            <p><strong>Bookkeeping Software:</strong> <span id="modal-bookkeeping_software"></span></p>
          </div>
          <div class="col-6">
            <p><strong>Management Account:</strong> <span id="modal-management_account"></span></p>
            <p><strong>Bank Accounts:</strong> <span id="modal-bank_accounts"></span></p>
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
      $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
      var url = "{{URL::to('/admin/quotation-list')}}";
      $("#contentContainer").on('click','#deleteBtn', function(){
          if(!confirm('Sure?')) return;
          codeid = $(this).attr('rid');
          info_url = url + '/'+codeid;
          $.ajax({
              url:info_url,
              method: "GET",
              type: "DELETE",
              data:{
              },
              success: function(d){
                  if(d.status == 300) {
                      alert(d.message);
                  } else if(d.status == 404) {
                      alert(d.message);
                  } else if(d.status == 303) {
                      alert(d.message);
                  }
                  location.reload();
              },
              error:function(d){
                  console.log(d);
              }
          });
      });

      $('.viewQuotation').on('click', function() {
        var name = $(this).data('name');
        var email = $(this).data('email');
        var company = $(this).data('company');
        var phone = $(this).data('phone');
        var businessType = $(this).data('business_type');
        var turnover = $(this).data('turnover');
        var vatReturns = $(this).data('vat_returns');
        var payroll = $(this).data('payroll');
        var bookkeeping = $(this).data('bookkeeping');
        var bookkeepingSoftware = $(this).data('bookkeeping_software');
        var managementAccount = $(this).data('management_account');
        var bankAccounts = $(this).data('bank_accounts');

        $('#modal-name').text(name);
        $('#modal-email').text(email);
        $('#modal-company').text(company);
        $('#modal-phone').text(phone);
        $('#modal-business_type').text(businessType);
        $('#modal-turnover').text(turnover);
        $('#modal-vat_returns').text(vatReturns);
        $('#modal-payroll').text(payroll);
        $('#modal-bookkeeping').text(bookkeeping);
        $('#modal-bookkeeping_software').text(bookkeepingSoftware);
        $('#modal-management_account').text(managementAccount);
        $('#modal-bank_accounts').text(bankAccounts);
      });
  });
</script>

@endsection