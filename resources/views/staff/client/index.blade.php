@extends('staff.layouts.staff')

@section('content')

<section class="section dashboard">
    <div class="row">
        <div class="col-lg-12 px-0 border shadow-sm">
            <p class="p-2 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center">
                <i class="bx bxs-user-plus fs-4 me-2"></i> Client List
            </p>
            <div class="table-wrapper my-4 mx-auto" style="width: 95%;">
                <table class="table cell-border table-bordered table-striped" id="clientsTable">
                    <thead>
                        <tr>
                            <th scope="col">sl</th>
                            <th scope="col">Reference</th>
                            <th scope="col">Name</th>
                            <th scope="col">Manager</th>
                            <th scope="col">Phone</th>
                            <th scope="col">Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- DataTables  -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

@endsection

@section('script')

<!-- Client Data table start -->
<script>
    $(document).ready(function() {
        var table = $('#clientsTable').DataTable({
            serverSide: true,
            ajax: "{{ route('get.Clients.staff') }}",
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex' },
                { data: 'refid', name: 'refid' },
                { data: 'name', name: 'name' },
                { 
                    data: 'manager.first_name', 
                    name: 'manager.first_name', 
                    defaultContent: ''
                },
                { data: 'phone', name: 'phone', defaultContent: '' },
                { data: 'email', name: 'email', defaultContent: '' },
            ]
        });
    });
</script>
<!-- Client Data table end -->

@endsection