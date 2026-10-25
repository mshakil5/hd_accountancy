@extends('admin.layouts.admin')

@section('content')
<div class="pagetitle d-flex justify-content-between align-items-center"><h1>Lead #{{ $lead->id }}</h1><a href="{{ route('admin.leads.index') }}" class="btn btn-outline-secondary">Back to leads</a></div>
<section class="section">
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<div class="row">
    <div class="col-lg-5">
        <div class="card"><div class="card-body pt-3">
            <h5 class="card-title">{{ $lead->name ?: 'WhatsApp prospect' }} <span class="badge bg-secondary">{{ str_replace('_',' ',$lead->qualification_status) }}</span>@if($lead->automation_completed_at) <span class="badge bg-info text-dark">Automation complete</span>@endif @if($lead->human_takeover) <span class="badge bg-primary">Human takeover</span>@endif</h5>
            <dl class="row mb-0">
                <dt class="col-5">WhatsApp</dt><dd class="col-7">+{{ $lead->whatsapp_id }}</dd>
                <dt class="col-5">Requirement</dt><dd class="col-7">{{ config('lead_qualification.intent_options.'.$lead->intent, $lead->intent ?: '—') }}</dd>
                <dt class="col-5">Person</dt><dd class="col-7">{{ config('lead_qualification.person_options.'.$lead->person_type, $lead->person_type ?: '—') }}</dd>
                <dt class="col-5">Current accountant</dt><dd class="col-7">{{ config('lead_qualification.accountant_options.'.$lead->current_accountant, $lead->current_accountant ?: '—') }}</dd>
                <dt class="col-5">Timeframe</dt><dd class="col-7">{{ config('lead_qualification.timeframe_options.'.$lead->contact_timeframe, $lead->contact_timeframe ?: '—') }}</dd>
                <dt class="col-5">Campaign</dt><dd class="col-7">{{ $lead->campaign_name ?: $lead->ad_name ?: $lead->ad_headline ?: 'Direct / unknown' }}</dd>
                <dt class="col-5">Assigned</dt><dd class="col-7">{{ optional($lead->assignee)->name ?: 'Unassigned' }}</dd>
            </dl>
            @if($lead->ai_summary)<div class="alert alert-light border mt-3"><strong>Summary</strong><br>{{ $lead->ai_summary }}</div>@endif
            <div class="d-flex gap-2 flex-wrap">
                @if(!$lead->human_takeover)<form method="POST" action="{{ route('admin.leads.takeover',$lead) }}">@csrf<button class="btn btn-primary">Take over</button></form>@else<form method="POST" action="{{ route('admin.leads.release',$lead) }}">@csrf<button class="btn btn-outline-primary">Release takeover</button></form>@endif
                <form method="POST" action="{{ route('admin.leads.notify',$lead) }}">@csrf<button class="btn btn-outline-secondary">Resend alert</button></form>
            </div>
            @if($lead->automation_completed_at && !$lead->human_takeover)
            <div class="alert alert-info mt-3 mb-0">The bot has finished qualifying this lead. New replies are stored and the team is alerted; the bot will not re-qualify.</div>
            @endif
        </div></div>
        <div class="card"><div class="card-body pt-3">
            <form method="POST" action="{{ route('admin.leads.update',$lead) }}">@csrf @method('PUT')
                <label class="form-label">Qualification status</label><select class="form-select mb-2" name="qualification_status">@foreach(['NEW','QUALIFYING','GENUINE','REVIEW','REJECTED','EXISTING_CLIENT','NON_SALES'] as $s)<option value="{{ $s }}" @selected($lead->qualification_status===$s)>{{ $s }}</option>@endforeach</select>
                <label class="form-label">Sales status</label><select class="form-select mb-2" name="sales_status">@foreach(['NEW','CONTACTED','CONSULTATION_BOOKED','PROPOSAL_SENT','WON','LOST','CLOSED'] as $s)<option value="{{ $s }}" @selected($lead->sales_status===$s)>{{ $s }}</option>@endforeach</select>
                <label class="form-label">Internal notes</label><textarea class="form-control mb-2" rows="4" name="admin_notes">{{ $lead->admin_notes }}</textarea>
                <button class="btn btn-success">Save</button>
            </form>
        </div></div>
    </div>
    <div class="col-lg-7">
        <div class="card"><div class="card-body pt-3">
            <h5 class="card-title">WhatsApp conversation</h5>
            <div style="height:520px;overflow:auto;background:#f6f7f9;padding:15px;border-radius:8px;">
                @forelse($lead->messages as $m)
                <div class="d-flex mb-2 {{ $m->direction==='outbound'?'justify-content-end':'justify-content-start' }}">
                    <div style="max-width:80%;" class="p-2 rounded {{ $m->direction==='outbound'?'bg-primary text-white':'bg-white border' }}">
                        <div>{{ $m->body ?: '['.$m->message_type.']' }}</div>
                        <div class="small {{ $m->direction==='outbound'?'text-white-50':'text-muted' }}">{{ $m->sender_type }} · {{ $m->created_at->format('d M H:i') }} @if($m->delivery_status)· {{ $m->delivery_status }}@endif</div>
                    </div>
                </div>
                @empty<div class="text-muted">No messages.</div>@endforelse
            </div>
            @if($lead->isWithinWhatsAppWindow())
            <form class="mt-3" method="POST" action="{{ route('admin.leads.send',$lead) }}">@csrf
                <div class="input-group"><textarea class="form-control" name="message" rows="2" placeholder="Reply as HD Accountancy" required></textarea><button class="btn btn-primary">Send WhatsApp</button></div>
                <div class="form-text">Free-form messages are available while the WhatsApp 24-hour customer-service window is open.</div>
            </form>
            @else
            <div class="alert alert-warning mt-3 mb-2">The 24-hour customer-service window is closed. Use an approved Meta template to re-open the conversation.</div>
            @if(config('meta_whatsapp.reengagement_template'))
            <form method="POST" action="{{ route('admin.leads.send-template',$lead) }}">@csrf<button class="btn btn-primary">Send approved re-engagement template</button></form>
            @endif
            @endif
        </div></div>
    </div>
</div>
</section>
@endsection
