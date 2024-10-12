@extends('admin.layouts.admin')

@section('content')

<section class="content" id="addThisFormContainer">
  <div class="container-fluid">
    <div class="row justify-content-md-center">
      <div class="col-md-10">
        <div class="card card-secondary">
          <div class="card-header">
            <h3 class="card-title">Contact Mail</h3>
          </div>
          <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if($contactMail)
              <form id="updateThisForm" action="{{ route('contactMail.update') }}" method="POST">
                @csrf
                <input type="hidden" name="id" value="{{ $contactMail->id }}">

                <div class="row">
                  <div class="col-sm-12">
                    <div class="form-group">
                      <label for="email">Email</label>
                      <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $contactMail->email) }}" required placeholder="Enter email">
                      @error('email')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                      @enderror
                    </div>
                  </div>
                </div>
              </form>
            @else
              <p class="alert alert-warning">No contact mail information found.</p>
            @endif
          </div>
          <div class="card-footer">
            @if($contactMail)
              <button type="submit" id="updateBtn" class="btn btn-secondary" value="Update" form="updateThisForm">Update</button>
              <div class="loader text-center" style="display: none;">
                  <div class="spinner-border text-primary" role="status">
                      <span class="visually-hidden">Loading...</span>
                  </div>
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

@endsection

@section('script')
<script>
    document.querySelector('#updateThisForm').addEventListener('submit', function() {
        document.querySelector('.loader').style.display = 'block';
        document.querySelector('#updateBtn').disabled = true;
    });
</script>
@endsection