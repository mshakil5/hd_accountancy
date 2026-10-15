@extends('admin.layouts.admin')
@section('content')
<div class="pagetitle"><h1>Lead Integration Health</h1></div>
<section class="section"><div class="card"><div class="card-body pt-3">
    <p>Webhook URL to configure in Meta:</p><code>{{ url('/api/webhooks/whatsapp') }}</code>
    <div class="table-responsive mt-3"><table class="table"><thead><tr><th>Check</th><th>Status</th></tr></thead><tbody>@foreach($checks as $label=>$ok)<tr><td>{{ $label }}</td><td>@if($ok)<span class="badge bg-success">Ready</span>@else<span class="badge bg-danger">Missing / attention</span>@endif</td></tr>@endforeach</tbody></table></div>
    <div class="alert alert-info">Credentials are intentionally read from the server environment and are not displayed here.</div>
    <a class="btn btn-secondary" href="{{ route('admin.leads.index') }}">Back to leads</a>
</div></div></section>
@endsection
