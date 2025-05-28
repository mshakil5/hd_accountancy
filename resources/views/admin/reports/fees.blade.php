@extends('admin.layouts.admin')

@section('content')
<section class="section">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 px-0">
                <div class="px-3">
                    <div class="row my-2">
                        <div class="col-lg-4">
                            <h3 class="fw-bold">Fees Report</h3>
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
                                                        <input class="form-check-input column-checkbox" type="checkbox" value="annual_agreed_fees" id="annual_agreed_fees" checked>
                                                        <label class="form-check-label" for="annual_agreed_fees">
                                                            Annual Agreed Fees
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input column-checkbox" type="checkbox" value="monthly_standing_order" id="monthly_standing_order" checked>
                                                        <label class="form-check-label" for="monthly_standing_order">
                                                            Monthly Standing Order
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check">
                                                        <input class="form-check-input column-checkbox" type="checkbox" value="monthly_amount" id="monthly_amount">
                                                        <label class="form-check-label" for="monthly_amount">
                                                            Monthly Amount
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input column-checkbox" type="checkbox" value="next_review" id="next_review">
                                                        <label class="form-check-label" for="next_review">
                                                            Next Review Date
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input column-checkbox" type="checkbox" value="comment" id="comment">
                                                        <label class="form-check-label" for="comment">
                                                            Comment
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="fw-bold">Filter by Standing Order:</label>
                                                <select class="form-control" id="standing_order_filter">
                                                    <option value="all">All Clients</option>
                                                    <option value="yes">With Standing Order</option>
                                                    <option value="no">Without Standing Order</option>
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
                                                <h5>With Standing Order: <span id="with-standing-order">0</span></h5>
                                            </div>
                                            <div class="col-md-4">
                                                <h5>Without Standing Order: <span id="without-standing-order">0</span></h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered" id="fees-report-table">
                                    <thead id="report-headings">
                                        <!-- Headings-->
                                    </thead>
                                    <tbody id="report-data">
                                        <!-- Data -->
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

        let standingOrderFilter = $('#standing_order_filter').val();

        $('#loader').show();

        $.ajax({
            url: "{{ route('reports.generate.fees') }}",
            type: "GET",
            data: {
                columns: selectedColumns,
                standing_order: standingOrderFilter
            },
            success: function(response) {
                $('#loader').hide();
                
                if (response.success) {
                    $('#stats-container').show();
                    $('#total-clients').text(response.stats.total_clients);
                    $('#with-standing-order').text(response.stats.with_standing_order);
                    $('#without-standing-order').text(response.stats.without_standing_order);

                    let headings = '<tr>';
                    response.columns.forEach(column => {
                        let headingText = '';
                        switch(column) {
                            case 'client_name': headingText = 'Client Name'; break;
                            case 'annual_agreed_fees': headingText = 'Annual Agreed Fees'; break;
                            case 'monthly_standing_order': headingText = 'Monthly Standing Order'; break;
                            case 'monthly_amount': headingText = 'Monthly Amount'; break;
                            case 'next_review': headingText = 'Next Review Date'; break;
                            case 'comment': headingText = 'Comment'; break;
                            case 'fees_discussion': headingText = 'Fees Discussion'; break;
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

        // Add data
        $('#report-data tr').each(function() {
            let row = [];
            $(this).find('td').each(function() {
                row.push($(this).text());
            });
            ws_data.push(row);
        });

        let ws = XLSX.utils.aoa_to_sheet(ws_data);
        XLSX.utils.book_append_sheet(wb, ws, "Fees Report");
        XLSX.writeFile(wb, "fees_report.xlsx");
    }
});
</script>
@endsection