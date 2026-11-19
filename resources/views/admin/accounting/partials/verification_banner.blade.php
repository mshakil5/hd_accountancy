@if($import->verification_status === 'verified')
    <div class="alert alert-success mt-3 mb-0">
        Verified. Reconciliation is unlocked for this statement.
        @if($import->verification_note)<div class="hd-small mt-1">Note: {{ $import->verification_note }}</div>@endif
        <div class="mt-2">
            <a href="{{ route('admin.bank-reconciliation.index', array_filter(['client_credential_id' => $client->client_credential_id ?? null, 'client_id' => $import->client_id])) }}"
               class="btn btn-success btn-sm">
                <i class="bi bi-bank mr-1"></i> Go to Bank Reconciliation &rarr;
            </a>
        </div>
    </div>
@else
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3 pt-2 border-top">
        <div>
            @if($blocking > 0)
                <span class="hd-small font-weight-bold" style="color:#b91c1c">
                    <i class="bi bi-exclamation-triangle-fill mr-1"></i> {{ $blocking }} blocking exception(s) must be resolved before verifying statement.
                </span>
            @elseif(in_array($import->verification_status, [null, 'pending'], true))
                <span class="hd-small font-weight-bold" style="color:#92400e">
                    <i class="bi bi-hourglass-split mr-1"></i> Not checked yet — click "Re-extract with OCR" or "Recalculate checks" to process this statement.
                </span>
            @else
                <span class="hd-small text-success font-weight-bold">
                    <i class="bi bi-check-circle-fill mr-1"></i> All checks passed!
                </span>
            @endif
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <form method="POST" action="{{ route('admin.bank-statements.verify-all-rows', $import->id) }}"
                  data-hd-ajax="1" data-hd-confirm="Verify all unverified rows for this statement? This marks them ready for reconciliation.">
                @csrf
                <button type="submit" class="btn btn-outline-success btn-sm"><i class="bi bi-check-all mr-1"></i> Verify all rows</button>
            </form>
        </div>
    </div>
@endif
