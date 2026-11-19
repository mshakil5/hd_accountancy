<div class="hd-rows">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
        <div class="hd-small">Corrections are saved with the original extracted value, your corrected value, your user and the reason.</div>
        <button type="button" class="btn btn-outline-primary btn-sm" data-hd-add="top">
            <i class="bi bi-plus-lg mr-1"></i> Add missing row
        </button>
    </div>

    <div class="hd-scroll">
        <table class="hd-table hd-verify-table">
            <thead>
                <tr>
                    <th style="width:50px; text-align:center;">Page</th>
                    <th style="width:85px;">Date</th>
                    <th>Description</th>
                    <th style="width:90px; text-align:right;">Amount</th>
                    <th style="width:95px; text-align:right;">PDF Bal</th>
                    <th style="width:95px; text-align:right;">System Bal</th>
                    <th style="width:90px; text-align:center;">Status</th>
                    <th style="width:200px; text-align:center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $entry)
                    @include('admin.accounting.partials.verification_row', ['entry' => $entry])
                @empty
                    <tr><td colspan="8" class="hd-small text-center py-3">No rows extracted. Add a row manually if the PDF is readable.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
