@extends('admin.layouts.admin')

@section('content')
<div class="pagetitle"><h1>Meta / WhatsApp Leads</h1></div>
<section class="section">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <div class="row mb-3">
        @foreach(['NEW','QUALIFYING','GENUINE','REVIEW','REJECTED','EXISTING_CLIENT','NON_SALES'] as $s)
        <div class="col-xl col-md-3 col-6 mb-2"><a class="text-decoration-none" href="{{ route('admin.leads.index',['status'=>$s]) }}"><div class="card h-100"><div class="card-body py-3"><div class="small text-muted">{{ str_replace('_',' ',$s) }}</div><div class="fs-4 fw-bold">{{ $counts[$s] ?? 0 }}</div></div></div></a></div>
        @endforeach
    </div>
    <div class="card">
        <div class="card-body pt-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <form class="d-flex gap-2" method="GET">
                    <input class="form-control" name="q" placeholder="Search lead" value="{{ request('q') }}">
                    <select class="form-select" name="status"><option value="">All qualification statuses</option>@foreach(['NEW','QUALIFYING','GENUINE','REVIEW','REJECTED','EXISTING_CLIENT','NON_SALES'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ str_replace('_',' ',$s) }}</option>@endforeach</select>
                    <button class="btn btn-primary">Filter</button>
                </form>
                <div class="d-flex gap-2">
                    <a class="btn btn-outline-secondary" href="{{ route('admin.leads.health') }}">Integration health</a>
                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#clearLeadsModal">Delete all leads</button>
                </div>
            </div>
            <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Lead</th><th>Requirement</th><th>Status</th><th>Timeframe</th><th>Campaign</th><th>Last message</th><th></th></tr></thead>
                <tbody>
                @forelse($leads as $lead)
                    <tr>
                        <td><strong>{{ $lead->name ?: 'WhatsApp prospect' }}</strong><br><span class="text-muted small">+{{ $lead->whatsapp_id }}</span></td>
                        <td>{{ config('lead_qualification.intent_options.'.$lead->intent, $lead->intent ?: '—') }}</td>
                        <td><span class="badge {{ $lead->qualification_status==='GENUINE'?'bg-success':($lead->qualification_status==='REVIEW'?'bg-warning text-dark':($lead->qualification_status==='REJECTED'?'bg-danger':'bg-secondary')) }}">{{ str_replace('_',' ',$lead->qualification_status) }}</span>@if($lead->human_takeover)<br><span class="badge bg-primary mt-1">Human takeover</span>@endif@if($lead->automation_completed_at)<br><span class="badge bg-info text-dark mt-1">Automation complete</span>@endif</td>
                        <td>{{ config('lead_qualification.timeframe_options.'.$lead->contact_timeframe, $lead->contact_timeframe ?: '—') }}</td>
                        <td>{{ $lead->campaign_name ?: $lead->ad_name ?: $lead->ad_headline ?: 'Direct / unknown' }}</td>
                        <td>{{ optional($lead->last_inbound_at)->diffForHumans() ?: '—' }}</td>
                        <td><a class="btn btn-sm btn-primary" href="{{ route('admin.leads.show',$lead) }}">Open</a></td>
                    </tr>
                @empty<tr><td colspan="7" class="text-center text-muted py-4">No leads found.</td></tr>@endforelse
                </tbody>
            </table>
            </div>
            {{ $leads->links() }}
        </div>
    </div>

    <div class="modal fade" id="clearLeadsModal" tabindex="-1" aria-labelledby="clearLeadsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.leads.clear') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="clearLeadsModalLabel">Delete all leads?</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>This permanently deletes <strong>{{ $counts->sum() }}</strong> lead(s) and every WhatsApp message linked to them.</p>
                        <p class="text-danger fw-semibold">This cannot be undone. Genuine, existing-client and won leads are included.</p>
                        <label class="form-label" for="clear-leads-confirmation">Type <strong>DELETE</strong> to confirm</label>
                        <input class="form-control" id="clear-leads-confirmation" name="confirmation" autocomplete="off" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-danger">Delete all leads</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
