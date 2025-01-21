@extends('admin.layouts.admin')

@section('content')

<section class="section">
    <div class="row">
        <div class="col-lg-12 px-0 shadow-sm border-theme border-2">
            <p class="p-2 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center">
                <i class='bx bxs-user-plus fs-4 me-2'></i> Client Details
            </p>

            <div class="row px-3 mt-4">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body border-theme border-2">
                            <div class="tab-content pt-2">
                                <div class="tab-pane fade show active">
                                    @include('admin.client.client_report')
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