  @php
    $absAmt     = abs((float)$entry->amount);
    $isCredit   = (float)$entry->amount >= 0;
    $approved   = ($entry->reconciliation_status ?? '') === 'approved';
    $dateFormatted = \Carbon\Carbon::parse($entry->transaction_date)->format('d/m/Y');
    $bankName   = optional($bankAccounts->firstWhere('id', $entry->bank_account_id))->bank_name ?? 'Unassigned';
    $importInfo = $importMap[$entry->bank_import_id] ?? null;

    // Exact-amount matches from the eligible receipts collection
    $exact = $fuzzyMatches[(int)$entry->id] ?? collect();
    // Auto-select if only one match
    $autoMatch = $exact->count() === 1 ? $exact->first() : null;
  @endphp

  <div class="hd-reconcile-row" id="bank-entry-{{ $entry->id }}">
    {{-- ── LEFT: bank transaction details ── --}}
    <div class="hd-bank-side">
      <div class="hd-entry-date">{{ $dateFormatted }}</div>
      <div class="hd-entry-desc">{{ $entry->description }}</div>
      <div class="hd-entry-bank"><i class="bi bi-bank mr-1"></i>{{ $bankName }}</div>

      <span class="hd-credit-label {{ $isCredit ? 'hd-credit' : 'hd-debit' }}">
        @if($isCredit)
          <i class="fa fa-arrow-down mr-1"></i>Credit
        @else
          <i class="fa fa-arrow-up mr-1"></i>Debit
        @endif
      </span>
      <span class="hd-amount-big" style="color:{{ $isCredit ? '#15803d' : '#b91c1c' }}">
        {{ $isCredit ? '+' : '-' }}£{{ number_format($absAmt, 2) }}
      </span>

      @if($approved)
        <span class="hd-pill" style="background:#dcfce7;color:#15803d">✓ Reconciled</span>
      @else
        <span class="hd-pill" style="background:#fee2e2;color:#b91c1c">Unreconciled</span>
      @endif

      {{-- View Details toggle --}}
      <button type="button" class="hd-details-toggle" onclick="hdToggleDetails(this)">
        <i class="fa fa-chevron-right fa-xs"></i> View Details
      </button>
      <div class="hd-details-panel">
        @if($entry->reference)
        <div class="hd-details-row"><span>Reference</span><span>{{ $entry->reference }}</span></div>
        @endif
        <div class="hd-details-row"><span>Transaction ID</span><span>#{{ $entry->id }}</span></div>
        @if($entry->balance_after !== null)
        <div class="hd-details-row"><span>PDF Balance</span><span>£{{ number_format((float)$entry->balance_after, 2) }}</span></div>
        @endif
        @if($entry->system_balance !== null)
        <div class="hd-details-row"><span>System Balance</span><span>£{{ number_format((float)$entry->system_balance, 2) }}</span></div>
        @endif
        @if($importInfo)
        <div class="hd-details-row"><span>Statement</span><span>{{ $importInfo->original_filename ?? '#'.$importInfo->id }}</span></div>
        @endif
        @if($entry->source_page || $entry->source_position)
        <div class="hd-details-row"><span>Page / Row</span><span>{{ $entry->source_page }}/{{ $entry->source_position }}</span></div>
        @endif
        @if($entry->extraction_source)
        <div class="hd-details-row"><span>Extraction</span><span>{{ ucfirst($entry->extraction_source) }}</span></div>
        @endif
        @if($entry->approved_at)
        <div class="hd-details-row"><span>Reconciled at</span><span>{{ \Carbon\Carbon::parse($entry->approved_at)->format('d/m/Y H:i') }}</span></div>
        @endif
      </div>
    </div>

    {{-- ── RIGHT: action area ── --}}
    <div class="hd-action-side">
      @if($approved)
        {{-- Reconciled state --}}
        <div class="hd-reconciled-banner mb-3">
          <div>
            <strong style="color:#15803d">✓ Reconciled</strong>
            @if($entry->approved_at)
              <span class="hd-small ml-2">on {{ \Carbon\Carbon::parse($entry->approved_at)->format('d/m/Y') }}</span>
            @endif
            @if($entry->receipt_id)
              <span class="hd-small ml-2">· Receipt #{{ $entry->receipt_id }}</span>
            @endif
            @if($entry->journal_posted_at)
              <span class="hd-pill ml-2" style="background:#dbeafe;color:#1d4ed8">Posted to ledger</span>
            @endif
          </div>
          <form method="POST" action="{{ route('admin.bank-reconciliation.action', $entry->id) }}" class="hd-action-form">
            @csrf
            <input type="hidden" name="client_id" value="{{ $entry->client_id }}">
            <input type="hidden" name="action" value="unmatch">
            <button class="btn btn-outline-danger btn-sm" onclick="return confirm('Undo this reconciliation?')">Undo</button>
          </form>
        </div>

        {{-- Show split allocations if any --}}
        @php $entrySplits = $splits[$entry->id] ?? collect(); @endphp
        @if($entrySplits->isNotEmpty())
        <div class="hd-small font-weight-bold mb-1">Split allocations ({{ $entrySplits->count() }}):</div>
        <div class="hd-scroll mb-2">
          <table class="hd-table" style="background:#f8fafc;border-radius:6px">
            <thead><tr><th>Amount</th><th>Receipt</th><th>Memo</th></tr></thead>
            <tbody>
              @foreach($entrySplits as $sp)
              <tr>
                <td><strong>£{{ number_format((float)$sp->amount, 2) }}</strong></td>
                <td>{{ $sp->receipt_id ? '#'.$sp->receipt_id : '—' }}</td>
                <td>{{ $sp->memo ?: '—' }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        @endif

      @else
        {{-- ── TABS ── --}}
        <div class="hd-row-tabs" role="tablist">
          <button type="button" class="hd-tab active" data-hd-tab="match">Match</button>
          <button type="button" class="hd-tab" data-hd-tab="create">Create</button>
          <button type="button" class="hd-tab" data-hd-tab="split">Split</button>
          <button type="button" class="hd-tab" data-hd-tab="find">Find &amp; Match</button>
          <button type="button" class="hd-tab" data-hd-tab="transfer">Transfer</button>
          <button type="button" class="hd-tab" data-hd-tab="discuss">Discuss</button>
        </div>

        {{-- ── TAB: Match ── --}}
        <section class="hd-tab-panel" data-hd-panel="match">
          @if($exact->isEmpty())
            <div class="alert alert-light py-2 mb-2" style="border:1px solid #e2e8f0;font-size:13px">
              <i class="fa fa-info-circle mr-1 text-muted"></i> No receipts with an exact matching amount (£{{ number_format($absAmt,2) }}) found. Use <strong>Find &amp; Match</strong> to search manually, or <strong>Create</strong> a new receipt.
            </div>
          @else
          <p class="hd-small mb-2">
            <strong>Suggested receipts</strong> — same amount (£{{ number_format($absAmt,2) }}), any date, nearest date first.
            @if($autoMatch)
              <strong>Auto-selected</strong> (only 1 match).
            @endif
          </p>
          <form method="POST" action="{{ route('admin.bank-reconciliation.action', $entry->id) }}" class="hd-action-form" id="match-form-{{ $entry->id }}">
            @csrf
            <input type="hidden" name="client_id" value="{{ $entry->client_id }}">
            <input type="hidden" name="action" value="match">

            @if($autoMatch)
              <input type="hidden" name="receipt_id" value="{{ $autoMatch->id }}">
              <div class="hd-match-item selected mb-2" data-hd-receipt="{{ $autoMatch->id }}" data-hd-client="{{ $entry->client_id }}" title="Click to view receipt details">
                <div>
                  <div style="font-size:13px;font-weight:700">{{ $autoMatch->supplier ?: 'Unknown Supplier' }}</div>
                  <div class="hd-match-meta">
                    #{{ $autoMatch->receipt_number }}
                    @if($autoMatch->detail && $autoMatch->detail->accountHead)
                      · {{ $autoMatch->detail->accountHead->code }} – {{ $autoMatch->detail->accountHead->name }}
                    @endif
                    @if($autoMatch->detail && $autoMatch->detail->invoice_date)
                      · {{ \Carbon\Carbon::parse($autoMatch->detail->invoice_date)->format('d/m/Y') }}
                    @endif
                    @if($autoMatch->detail && $autoMatch->detail->payment_method)
                      · {{ ucfirst($autoMatch->detail->payment_method) }}
                    @endif
                    @php $gap = $suggestionMeta[(int)$entry->id][(int)$autoMatch->id]['days'] ?? null; @endphp
                    @if($gap === 0)
                      <span class="hd-pill" style="background:#dcfce7;color:#15803d">Same date</span>
                    @elseif($gap !== null)
                      <span class="hd-pill" style="background:#fef3c7;color:#b45309">{{ $gap }} day{{ $gap === 1 ? '' : 's' }} apart</span>
                    @endif
                  </div>
                </div>
                <div class="hd-match-amount" style="color:#15803d">£{{ number_format((float)$autoMatch->detail->total_amount, 2) }}<i class="fa fa-eye hd-receipt-eye" title="View receipt details"></i></div>
              </div>
            @else
              @foreach($exact->take(10) as $r)
              <label class="hd-match-item" style="cursor:pointer;display:flex" data-hd-receipt="{{ $r->id }}" data-hd-client="{{ $entry->client_id }}" title="Click to view receipt details">
                <input type="radio" name="receipt_id" value="{{ $r->id }}" style="margin-right:8px;margin-top:2px" {{ $loop->first ? 'checked' : '' }}>
                <div style="flex:1;min-width:0">
                  <div style="font-size:13px;font-weight:700">{{ $r->supplier ?: 'Unknown Supplier' }}</div>
                  <div class="hd-match-meta">
                    #{{ $r->receipt_number }}
                    @if($r->detail && $r->detail->accountHead)
                      · {{ $r->detail->accountHead->code }} – {{ $r->detail->accountHead->name }}
                    @endif
                    @if($r->detail && $r->detail->invoice_date)
                      · {{ \Carbon\Carbon::parse($r->detail->invoice_date)->format('d/m/Y') }}
                    @endif
                    @if($r->detail && $r->detail->payment_method)
                      · {{ ucfirst($r->detail->payment_method) }}
                    @endif
                    @php $gap = $suggestionMeta[(int)$entry->id][(int)$r->id]['days'] ?? null; @endphp
                    @if($gap === 0)
                      <span class="hd-pill" style="background:#dcfce7;color:#15803d">Same date</span>
                    @elseif($gap !== null)
                      <span class="hd-pill" style="background:#fef3c7;color:#b45309">{{ $gap }} day{{ $gap === 1 ? '' : 's' }} apart</span>
                    @endif
                  </div>
                </div>
                <div class="hd-match-amount" style="color:#15803d">£{{ number_format((float)$r->detail->total_amount, 2) }}<i class="fa fa-eye hd-receipt-eye" title="View receipt details"></i></div>
              </label>
              @endforeach
            @endif

            <button class="btn btn-primary btn-sm mt-2" onclick="return confirm('Match this receipt to the bank transaction?')">
              <i class="fa fa-link mr-1"></i> Match &amp; Reconcile
            </button>
          </form>
          @endif
        </section>

        {{-- ── TAB: Create ── --}}
        <section class="hd-tab-panel" data-hd-panel="create" hidden>
          <p class="hd-small mb-2">Create a new receipt from this bank transaction and reconcile immediately. The bank amount is the VAT-inclusive total; the net amount follows the VAT %.</p>
          <form method="POST" action="{{ route('admin.bank-reconciliation.action', $entry->id) }}" class="hd-action-form">
            @csrf
            <input type="hidden" name="client_id" value="{{ $entry->client_id }}">
            <input type="hidden" name="action" value="create">
            <div class="hd-form-grid">
              <div class="hd-field">
                <label class="hd-field-lbl">Supplier</label>
                <input class="form-control hd-fc" name="supplier" value="{{ $entry->description }}" placeholder="Supplier name" required>
              </div>
              <div class="hd-field">
                <label class="hd-field-lbl">Invoice Date</label>
                <input class="form-control hd-fc" type="date" name="invoice_date" value="{{ $entry->transaction_date }}" required>
              </div>
              {{-- Net Amount · VAT Percent · Gross Amount (same block as Split / Find & Match) --}}
              @include('admin.partials.receipt_amounts', [
                  'gross' => number_format($absAmt, 2, '.', ''),
                  'grossName' => 'gross',
                  'grossClass' => '',
                  'vatName' => 'vat_percent',
                  'grossHint' => 'must equal the bank amount £' . number_format($absAmt, 2),
              ])
              <div class="hd-field">
                <label class="hd-field-lbl">
                  Account Head
                  <button type="button" class="hd-qadd-btn" onclick="openQuickHeadFor(this)"><i class="fa fa-plus-circle"></i> Quick Add</button>
                </label>
                <select class="form-control select2 hd-fc" name="account_head_id" required>
                  <option value="">Choose account head</option>
                  @foreach($heads as $h)
                  <option value="{{ $h->id }}">{{ $h->code }} — {{ $h->name }}</option>
                  @endforeach
                </select>
              </div>
              <div style="grid-column:1/-1">
                <button class="btn btn-success btn-sm" onclick="return confirm('Create receipt and reconcile for £{{ number_format($absAmt,2) }}?')">
                  <i class="fa fa-plus mr-1"></i> Create &amp; Reconcile
                </button>
              </div>
            </div>
          </form>
        </section>

        {{-- ── TAB: Split ── --}}
        <section class="hd-tab-panel" data-hd-panel="split" hidden>
          <p class="hd-small mb-2">Split this £{{ number_format($absAmt, 2) }} transaction across multiple receipts. All lines must total exactly £{{ number_format($absAmt, 2) }}.</p>
          <form method="POST" action="{{ route('admin.bank-reconciliation.action', $entry->id) }}"
                class="hd-split-form hd-action-form"
                data-split-total="{{ number_format($absAmt, 2, '.', '') }}">
            @csrf
            <input type="hidden" name="client_id" value="{{ $entry->client_id }}">
            <input type="hidden" name="action" value="split">
            <div class="hd-split-lines">
              @foreach([0,1] as $i)
              <div class="hd-split-line hd-form-grid">
                <div class="hd-field">
                  <label class="hd-field-lbl">Supplier</label>
                  <input class="form-control hd-fc" name="splits[{{ $i }}][supplier]" placeholder="Supplier name">
                </div>
                <div class="hd-field">
                  <label class="hd-field-lbl">Invoice Date</label>
                  <input class="form-control hd-fc" type="date" name="splits[{{ $i }}][invoice_date]" value="{{ $entry->transaction_date }}">
                </div>
                @include('admin.partials.receipt_amounts', [
                    'gross' => '',
                    'grossName' => 'splits[' . $i . '][amount]',
                    'grossClass' => 'hd-split-amount',
                    'vatName' => 'splits[' . $i . '][vat_percent]',
                ])
                <div class="hd-field">
                  <label class="hd-field-lbl">
                    Account Head
                    <button type="button" class="hd-qadd-btn" onclick="openQuickHeadFor(this)"><i class="fa fa-plus-circle"></i> Quick Add</button>
                  </label>
                  <select class="form-control select2 hd-fc" name="splits[{{ $i }}][account_head_id]" required>
                    <option value="">Choose head</option>
                    @foreach($heads as $h)
                    <option value="{{ $h->id }}">{{ $h->code }} — {{ $h->name }}</option>
                    @endforeach
                  </select>
                </div>
              </div>
              @endforeach
            </div>
            <div class="d-flex gap-2 align-items-center flex-wrap mt-2 mb-2">
              <button type="button" class="btn btn-outline-secondary btn-sm" onclick="hdAddSplitLine(this)"><i class="fa fa-plus"></i> Add line</button>
              <button type="button" class="btn btn-outline-secondary btn-sm" onclick="hdRemoveSplitLine(this)" disabled><i class="fa fa-minus"></i> Remove line</button>
              <span class="hd-split-meter" role="status">Allocated £0.00 of £{{ number_format($absAmt, 2) }}</span>
            </div>
            <button class="btn btn-primary btn-sm" onclick="return hdConfirmSplit(this.closest('form'))">
              <i class="fa fa-scissors mr-1"></i> Save Split &amp; Reconcile
            </button>
          </form>
        </section>

        {{-- ── TAB: Find & Match ── --}}
        <section class="hd-tab-panel" data-hd-panel="find" hidden>
          <p class="hd-small mb-2">Search and select receipts to match. Suggested receipts (same amount, any date) are listed first, nearest date first. An existing receipt can give only part of what is left of it, and new receipts below cover the remainder — any combination, as long as everything adds up to £{{ number_format($absAmt, 2) }}.</p>
          <div class="hd-find-counter" id="find-counter-{{ $entry->id }}">
            Selected: £0.00 / Target: £{{ number_format($absAmt, 2) }} · Remaining: £{{ number_format($absAmt, 2) }}
          </div>
          <input type="search" class="form-control mb-2 hd-find-search" placeholder="Search supplier or receipt #" aria-label="Search">
          <form method="POST" action="{{ route('admin.bank-reconciliation.action', $entry->id) }}" class="hd-action-form hd-find-form" id="find-form-{{ $entry->id }}">
            @csrf
            <input type="hidden" name="client_id" value="{{ $entry->client_id }}">
            <input type="hidden" name="action" value="find_match">
            <div class="hd-find-list" data-target="{{ number_format($absAmt, 2, '.', '') }}">
              @forelse(($findMatches[(int)$entry->id] ?? $receipts) as $r)
              @php
                $rAmt = (float)($r->detail->total_amount ?? 0);
                $rLeft = (float)($receiptRemaining[(int)$r->id] ?? $rAmt);
              @endphp
              <label class="hd-find-item"
                     data-hd-receipt="{{ $r->id }}"
                     data-hd-client="{{ $entry->client_id }}"
                     title="Click to view receipt details"
                     data-search="{{ strtolower(($r->receipt_number ?? '').' '.($r->supplier ?? '')) }}"
                     data-amount="{{ number_format($rAmt, 2, '.', '') }}"
                     data-remaining="{{ number_format($rLeft, 2, '.', '') }}">
                <input type="checkbox" name="receipt_ids[]" value="{{ $r->id }}"
                       class="hd-find-cb"
                       data-amount="{{ $rAmt }}"
                       data-remaining="{{ $rLeft }}"
                       onchange="hdFindUpdate(this)">
                <div style="flex:1;min-width:0">
                  <span style="font-weight:700;font-size:13px">#{{ $r->receipt_number }} — {{ $r->supplier ?: 'Unknown' }}</span>
                  @if($r->detail && $r->detail->invoice_date)
                    <span class="hd-small ml-1">· {{ \Carbon\Carbon::parse($r->detail->invoice_date)->format('d/m/Y') }}</span>
                  @endif
                  @if($r->detail && $r->detail->payment_method)
                    <span class="hd-small ml-1">· {{ ucfirst($r->detail->payment_method) }}</span>
                  @endif
                  @if($r->detail && $r->detail->accountHead)
                    <span class="hd-small ml-1">· {{ $r->detail->accountHead->code }}</span>
                  @endif
                  @if(isset($suggestionMeta[(int)$entry->id][(int)$r->id]))
                    <span class="hd-pill" style="background:#e0f2fe;color:#0369a1">Suggested</span>
                  @endif
                  @if($rLeft < $rAmt - 0.004)
                    <span class="hd-pill" style="background:#fef3c7;color:#b45309">£{{ number_format($rLeft, 2) }} left</span>
                  @endif
                  <i class="fa fa-eye hd-receipt-eye" title="View receipt details"></i>
                </div>
                <span style="font-weight:700;color:#15803d;white-space:nowrap">£{{ number_format($rAmt, 2) }}</span>
                <input type="number" class="form-control hd-fc hd-find-apply"
                       name="allocations[{{ $r->id }}]" min="0.01" max="{{ number_format($rLeft, 2, '.', '') }}" step="0.01"
                       placeholder="Apply £" title="How much of this receipt to use" hidden disabled>
              </label>
              @empty
              <div class="hd-small text-muted">No eligible receipts found.</div>
              @endforelse
            </div>

            {{-- Create one or more new receipts for the uncovered remainder --}}
            <div class="hd-create-block">
              <div class="hd-small font-weight-bold mb-1">Create new receipt(s) for the remainder</div>
              <div class="hd-create-lines">
                <div class="hd-create-line hd-form-grid">
                  <div class="hd-field">
                    <label class="hd-field-lbl">Supplier</label>
                    <input class="form-control hd-fc" name="creates[0][supplier]" value="{{ $entry->description }}" placeholder="Supplier name">
                  </div>
                  <div class="hd-field">
                    <label class="hd-field-lbl">Invoice Date</label>
                    <input class="form-control hd-fc" type="date" name="creates[0][invoice_date]" value="{{ $entry->transaction_date }}">
                  </div>
                  @include('admin.partials.receipt_amounts', [
                      'gross' => '',
                      'grossName' => 'creates[0][gross]',
                      'grossClass' => 'hd-create-gross',
                      'vatName' => 'creates[0][vat_percent]',
                  ])
                  <div class="hd-field" style="grid-column:1/-1">
                    <label class="hd-field-lbl">
                      Account Head
                      <button type="button" class="hd-qadd-btn" onclick="openQuickHeadFor(this)"><i class="fa fa-plus-circle"></i> Quick Add</button>
                    </label>
                    <select class="form-control select2 hd-fc" name="creates[0][account_head_id]">
                      <option value="">Choose head</option>
                      @foreach($heads as $h)
                      <option value="{{ $h->id }}">{{ $h->code }} — {{ $h->name }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>
              </div>
              <div class="d-flex gap-2 align-items-center flex-wrap mt-1 mb-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="hdAddCreateLine(this)"><i class="fa fa-plus"></i> Add create line</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="hdRemoveCreateLine(this)" disabled><i class="fa fa-minus"></i> Remove line</button>
                <span class="hd-create-meter" role="status">Creating £0.00</span>
              </div>
            </div>

            <button class="btn btn-primary btn-sm mt-2 hd-find-submit" disabled onclick="return confirm('Match the applied receipts and create any new ones, then reconcile this transaction?')">
              <i class="fa fa-check mr-1"></i> Match / Create &amp; Reconcile
            </button>
          </form>
        </section>

        {{-- ── TAB: Transfer ── --}}
        <section class="hd-tab-panel" data-hd-panel="transfer" hidden>
          <p class="hd-small mb-2">Mark this movement as an internal transfer to another bank account.</p>
          <form method="POST" action="{{ route('admin.bank-reconciliation.action', $entry->id) }}" class="hd-action-form">
            @csrf
            <input type="hidden" name="client_id" value="{{ $entry->client_id }}">
            <input type="hidden" name="action" value="transfer">
            <div class="hd-field mb-3">
              <label class="hd-field-lbl">Destination Bank Account</label>
              <select class="form-control select2 hd-fc" name="destination_bank_account_id" required>
                <option value="">Choose destination</option>
                @foreach($bankAccounts as $bank)
                  @continue((int)$bank->id === (int)$entry->bank_account_id)
                  @continue(strtolower(trim($bank->bank_name)) === 'unknown historical bank')
                  <option value="{{ $bank->id }}">{{ $bank->bank_name }}{{ $bank->account_last_four?' ••'.$bank->account_last_four:'' }}</option>
                @endforeach
              </select>
            </div>
            <button class="btn btn-primary btn-sm" onclick="return confirm('Record this as an internal bank transfer?')">
              <i class="fa fa-exchange mr-1"></i> Save Transfer
            </button>
          </form>
        </section>

        {{-- ── TAB: Discuss ── --}}
        <section class="hd-tab-panel" data-hd-panel="discuss" hidden>
          @php $entryNotes = $notes[$entry->id] ?? collect(); @endphp
          @if($entryNotes->isNotEmpty())
          <div class="mb-2">
            <div class="hd-small font-weight-bold mb-1">Internal Notes</div>
            @foreach($entryNotes as $n)
            <div class="hd-note-pill">{{ $n->body }} <span style="opacity:.6;font-size:11px">· {{ $n->author_name ?? 'Unknown' }} · {{ \Carbon\Carbon::parse($n->created_at)->format('d/m/Y H:i') }}</span></div>
            @endforeach
          </div>
          @endif
          <form method="POST" action="{{ route('admin.bank-reconciliation.action', $entry->id) }}" class="hd-action-form">
            @csrf
            <input type="hidden" name="client_id" value="{{ $entry->client_id }}">
            <input type="hidden" name="action" value="add_note">
            <div class="hd-field mb-2">
              <label class="hd-field-lbl">Add Internal Note</label>
              <textarea class="form-control hd-fc" name="review_note" rows="2" maxlength="2000" placeholder="Accountant note…" required></textarea>
            </div>
            <button class="btn btn-outline-primary btn-sm"><i class="fa fa-comment mr-1"></i> Save Note</button>
          </form>
        </section>
      @endif
    </div>
  </div>
