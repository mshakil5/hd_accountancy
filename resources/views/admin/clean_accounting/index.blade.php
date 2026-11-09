@extends('admin.layouts.admin')

@section('content')

<section class="content">
    <div class="container-fluid">

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card card-danger">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-exclamation-triangle"></i>
                    Clean accounting data
                </h3>
            </div>

            <div class="card-body">
                <p class="mb-3">
                    This permanently deletes the accounting records below. Clients, users, services,
                    chart of accounts (account heads), account types and account sub types are
                    <strong>not</strong> touched.
                    Receipt files and bank statement PDFs are deleted from storage as well.
                    This action cannot be undone.
                </p>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 220px;">Area</th>
                                <th>Table</th>
                                <th class="text-end" style="width: 120px;">Rows</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($groups as $group => $tables)
                                @foreach ($tables as $table)
                                    <tr>
                                        @if ($loop->first)
                                            <td rowspan="{{ count($tables) }}" class="align-middle fw-bold">
                                                {{ $group }}
                                            </td>
                                        @endif
                                        <td><code>{{ $table }}</code></td>
                                        <td class="text-end">{{ number_format($counts[$table] ?? 0) }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-warning mt-3 mb-0">
                    <strong>Before you continue:</strong> take a database backup if this data may be needed later.
                </div>
            </div>

            <div class="card-footer">
                <form method="POST" action="{{ route('admin.clean-accounting.destroy') }}"
                    onsubmit="if(!confirm('This permanently deletes all accounting data listed above. Continue?')){return false;} this.querySelector('button[type=submit]').disabled=true; return true;">
                    @csrf

                    <div class="row align-items-end g-2">
                        <div class="col-md-4">
                            <label for="confirm" class="form-label">
                                Type <strong>CLEAN</strong> to confirm
                            </label>
                            <input type="text" id="confirm" name="confirm" class="form-control"
                                autocomplete="off" placeholder="CLEAN" required>
                        </div>

                        <div class="col-md-4">
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-trash"></i>
                                Clean accounting data
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>
</section>

@endsection
