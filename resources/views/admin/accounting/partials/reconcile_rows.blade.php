@forelse($entries as $entry)
  @include('admin.accounting.partials.reconcile_row', ['entry' => $entry])
@empty
  @include('admin.accounting.partials.reconcile_empty')
@endforelse
