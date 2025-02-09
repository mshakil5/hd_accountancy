@extends('admin.layouts.admin')

@section('content')

<section class="section" id="print-section">
    <div class="row">
        <div class="col-lg-12 px-0 shadow-sm border-theme border-2">
            <p class="p-2 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center justify-content-between">
                <span class="d-flex align-items-center">
                    <i class='bx bxs-user-plus fs-4 me-2'></i> Client Details
                </span>
                <button onclick="printSection()" class="btn btn-success btn-sm no-print">
                    <i class="bx bxs-printer fs-4 text-white"></i>
                </button>
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

<style>
    .no-print {
        background-color: #28a745;
        color: white;
        border: none;
        padding: 5px 15px;
        cursor: pointer;
    }

    .no-print:hover {
        background-color: #218838;
    }

    @media print {
        body * {
            visibility: hidden;
        }
        #print-section, #print-section * {
            visibility: visible;
        }
        #print-section {
            position: absolute;
            left: 0;
            top: 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            width: 100%;
            font-size: 12px;
        }
        .col-lg-3 {
            float: left;
            width: 30% !important;
        }
        .col-lg-9 {
            float: left;
            width: 70% !important;
        }
        .card, .card-body {
            box-shadow: none;
            border: none;
        }
        .no-print {
            display: none !important;
        }
        /* Remove any background color from the rest of the printed page */
        html, body {
            background: none !important;
            background-color: white !important;
        }
    }
</style>


@endsection

@section('script')
<script>
    function printSection() {
        window.print();
    }
</script>
@endsection