@extends('admin.layouts.admin')

@section('content')
<section class="section">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 px-0">
                <div class="px-3">
                    <div class="row my-2">
                        <div class="col-lg-4">
                            <h3 class="fw-bold">Client Acquisition Report</h3>
                        </div>
                    </div>

                    <div class="card shadow-sm mt-4 border-1 border-theme">
                        <div class="card-body py-4">
                            <div class="row mb-4">
                                <div class="col-lg-12">
                                    <h4 class="fw-bold">Report Options</h4>
                                    <div class="row mt-3">
                                        <div class="col-lg-6">
                                            <label class="fw-bold">Select Columns to Display:</label>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-check">
                                                        <input class="form-check-input column-checkbox" type="checkbox" value="client_name" id="client_name" checked>
                                                        <label class="form-check-label" for="client_name">
                                                            Client Name
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input column-checkbox" type="checkbox" value="client_type" id="client_type" checked>
                                                        <label class="form-check-label" for="client_type">
                                                            Client Type
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input column-checkbox" type="checkbox" value="annual_agreed_fees" id="annual_agreed_fees" checked>
                                                        <label class="form-check-label" for="annual_agreed_fees">
                                                            Annual Agreed Fees
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check">
                                                        <input class="form-check-input column-checkbox" type="checkbox" value="monthly_standing_order" id="monthly_standing_order" checked>
                                                        <label class="form-check-label" for="monthly_standing_order">
                                                            Monthly Standing Order
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input column-checkbox" type="checkbox" value="agreement_date" id="agreement_date">
                                                        <label class="form-check-label" for="agreement_date">
                                                            Agreement Date
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input column-checkbox" type="checkbox" value="cessation_date" id="cessation_date">
                                                        <label class="form-check-label" for="cessation_date">
                                                            Cessation Date
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="fw-bold">Start Date:</label>
                                                <input type="date" class="form-control" id="start_date">
                                            </div>
                                            <div class="form-group">
                                                <label class="fw-bold">End Date:</label>
                                                <input type="date" class="form-control" id="end_date">
                                            </div>
                                            <div class="form-group">
                                                <label class="fw-bold">Filter by Cessation Date:</label>
                                                <select class="form-control" id="cessation_filter">
                                                    <option value="all">All Clients</option>
                                                    <option value="active">Active Clients</option>
                                                    <option value="ceased">Ceased Clients</option>
                                                </select>
                                            </div>
                                            <button class="btn btn-primary mt-3" id="generate-report-btn">Generate Report</button>
                                            <button class="btn btn-success mt-3" id="export-excel-btn">Export to Excel</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-4" id="stats-container" style="display: none;">
                                <div class="col-lg-12">
                                    <div class="alert alert-info">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <h5>Total Clients: <span id="total-clients">0</span></h5>
                                            </div>
                                            <div class="col-md-4">
                                                <h5>Active Clients: <span id="active-clients">0</span></h5>
                                            </div>
                                            <div class="col-md-4">
                                                <h5>Ceased Clients: <span id="ceased-clients">0</span></h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered" id="acquisition-report-table">
                                    <thead id="report-headings">
                                    </thead>
                                    <tbody id="report-data">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div id="loader" style="display: none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); z-index:9999;">
    <div class="spinner-border text-primary spinner-border-lg" role="status" style="width: 4rem; height: 4rem;"></div>
</div>
@endsection

@section('script')
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
$(document).ready(function() {
    $('#generate-report-btn').click(function() {
        generateReport();
    });

    $('#export-excel-btn').click(function() {
        exportToExcel();
    });

    function generateReport() {
        let selectedColumns = [];
        $('.column-checkbox:checked').each(function() {
            selectedColumns.push($(this).val());
        });

        if (selectedColumns.length === 0) {
            alert('Please select at least one column to display');
            return;
        }

        let startDate = $('#start_date').val();
        let endDate = $('#end_date').val();
        let cessationFilter = $('#cessation_filter').val();

        $('#loader').show();

        $.ajax({
            url: "{{ route('reports.generate.acquisition') }}",
            type: "GET",
            data: {
                columns: selectedColumns,
                start_date: startDate,
                end_date: endDate,
                cessation_filter: cessationFilter
            },
            success: function(response) {
                $('#loader').hide();
                
                if (response.success) {
                    $('#stats-container').show();
                    $('#total-clients').text(response.stats.total_clients);
                    $('#active-clients').text(response.stats.active_clients);
                    $('#ceased-clients').text(response.stats.ceased_clients);

                    let headings = '<tr>';
                    response.columns.forEach(column => {
                        let headingText = '';
                        switch(column) {
                            case 'client_name': headingText = 'Client Name'; break;
                            case 'client_type': headingText = 'Client Type'; break;
                            case 'annual_agreed_fees': headingText = 'Annual Agreed Fees'; break;
                            case 'monthly_standing_order': headingText = 'Monthly Standing Order'; break;
                            case 'agreement_date': headingText = 'Agreement Date'; break;
                            case 'cessation_date': headingText = 'Cessation Date'; break;
                        }
                        headings += `<th>${headingText}</th>`;
                    });
                    headings += '</tr>';
                    $('#report-headings').html(headings);

                    let rows = '';
                    response.data.forEach(row => {
                        rows += '<tr>';
                        response.columns.forEach(column => {
                            rows += `<td>${row[column]}</td>`;
                        });
                        rows += '</tr>';
                    });
                    $('#report-data').html(rows);
                }
            },
            error: function(xhr) {
                $('#loader').hide();
                alert('Error generating report: ' + xhr.responseText);
            }
        });
    }

    function exportToExcel() {
        if ($('#report-data').html().trim() === '') {
            alert('Please generate the report first');
            return;
        }

        let wb = XLSX.utils.book_new();
        let ws_data = [];

        let headings = [];
        $('#report-headings th').each(function() {
            headings.push($(this).text());
        });
        ws_data.push(headings);

        $('#report-data tr').each(function() {
            let row = [];
            $(this).find('td').each(function() {
                row.push($(this).text());
            });
            ws_data.push(row);
        });

        let ws = XLSX.utils.aoa_to_sheet(ws_data);
        XLSX.utils.book_append_sheet(wb, ws, "Acquisition Report");
        XLSX.writeFile(wb, "acquisition_report.xlsx");
    }
});
</script>
@endsection