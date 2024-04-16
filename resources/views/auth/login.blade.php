@extends('auth.app')

@section('content')
<section class="section dashboard">
  <div class="row">
    <div class="col-lg-12">
      <div class="row">
        <div class="col-lg-6 mx-auto">
          <div class="report-box border-theme sales-card p-4 rounded-4 mt-5">
            <div class="card-body">
              <div class="text-center mb-4 text-uppercase txt-theme fs-1 fw-bold">
                Login
              </div>
              <form class="needs-validation" method="POST" action="{{ route('login') }}">
                @csrf
                <div class="row w-75 mx-auto">
                  <div class="col-lg-12 d-flex align-items-center justify-content-around mb-3">
                    <div class="col-lg-3"><label for="email">Email:</label></div>
                    <div class="col-lg-9">
                      <input type="email" class="form-control @error('email') is-invalid @enderror" style="background-color: #D9D9D9;" name="email" id="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                      @error('email')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                      @enderror
                    </div>
                  </div>
                  <div class="col-lg-12 d-flex align-items-center justify-content-around">
                    <div class="col-lg-3"><label for="password">Password:</label></div>
                    <div class="col-lg-9">
                      <input type="password" class="form-control @error('password') is-invalid @enderror" style="background-color: #D9D9D9;" name="password" id="password" autocomplete="current-password" required>
                      @error('password')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                      @enderror
                    </div>
                  </div>
                  <small class="text-end mt-2">
                    <!-- <a href="" class="txt-theme me-2">Forgot Password?</a> -->
                  </small>
                  <div class="row mt-3">
                    <div class="col-md-12 text-center">
                      <button type="submit" class="btn fw-bold btn-sm py-1 fs-5 px-3 bg-theme text-light btn-outline-dark px-3 mx-2">Login</button>
                    </div>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
