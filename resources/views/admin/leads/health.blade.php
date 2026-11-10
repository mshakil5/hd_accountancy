@extends('admin.layouts.admin')
@section('content')
<div class="pagetitle"><h1>Lead Integration Health</h1></div>
<section class="section"><div class="card"><div class="card-body pt-3">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <p>Webhook URL to configure in Meta:</p><code>{{ url('/api/webhooks/whatsapp') }}</code>

    <div class="table-responsive mt-3"><table class="table align-middle"><thead><tr><th>Check</th><th>Status</th><th>Notes</th></tr></thead><tbody>
        @foreach($checks as $check)
        <tr>
            <td>{{ $check['label'] }}</td>
            <td>
                @if($check['state'] === 'ready')<span class="badge bg-success">Ready</span>
                @elseif($check['state'] === 'optional')<span class="badge bg-secondary">Optional</span>
                @else<span class="badge bg-danger">Missing</span>@endif
            </td>
            <td class="small text-muted">{{ $check['detail'] ?? '' }}</td>
        </tr>
        @endforeach
    </tbody></table></div>

    <div class="alert alert-info">Credentials are intentionally read from the server environment and are not displayed here.
        <strong>Green means the configuration is present.</strong> It does not prove the live Meta/WhatsApp journey works — complete the end-to-end test before go-live.</div>

    <h6 class="mt-4">Send a test alert</h6>
    <div class="d-flex gap-2 flex-wrap mb-3">
        <form method="POST" action="{{ route('admin.leads.test-alert') }}">@csrf<input type="hidden" name="channel" value="email"><button class="btn btn-outline-primary">Test email</button></form>
        <form method="POST" action="{{ route('admin.leads.test-alert') }}">@csrf<input type="hidden" name="channel" value="sms"><button class="btn btn-outline-primary">Test SMS</button></form>
        <form method="POST" action="{{ route('admin.leads.test-alert') }}">@csrf<input type="hidden" name="channel" value="whatsapp"><button class="btn btn-outline-primary">Test WhatsApp</button></form>
    </div>

    <div class="alert alert-secondary">
        <strong>Required background services</strong>
        <p class="mb-1 mt-2">Queue worker (the lead bot runs asynchronously):</p>
        <code>php artisan queue:work --queue=leads,default --tries=3 --timeout=90</code>
        <p class="mb-1 mt-3">Scheduler (runs <code>leads:prune</code> daily at 02:30):</p>
        <code>* * * * * cd /path/to/hd_backend &amp;&amp; php artisan schedule:run &gt;&gt; /dev/null 2&gt;&amp;1</code>
    </div>

    <div class="d-flex gap-2">
        <a class="btn btn-secondary" href="{{ route('admin.leads.index') }}">Back to leads</a>
        <a class="btn btn-outline-secondary" href="{{ route('admin.leads.ad-intents.index') }}">Campaign / Ad intent mapping</a>
    </div>
</div></div></section>
@endsection
