<section class="py-5 bg-light" id="contactForm">
  <div class="container">
    <div class="row py-2 col-lg-10 mx-auto">
      @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
      <h4 class="txt-primary text-center poppins-medium text-capitalize">Please fill up the information</h4>
      <div class="card p-4 pt-5 mt-4">
      <form method="POST" action="{{ route('frontend.contact.store') }}">
        @csrf

        <div class="row">
          <div class="col-lg-6 mb-4">
            <input type="text" name="name" placeholder="Name*" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
            @error('name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-lg-6 mb-4">
            <input type="email" name="email" placeholder="Email*" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
            @error('email')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-lg-6 mb-4">
            <input type="number" name="phone" placeholder="Phone*" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" required>
            @error('phone')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-lg-6 mb-4">
            <input type="text" name="business_name" placeholder="Business Name*" class="form-control @error('business_name') is-invalid @enderror" value="{{ old('business_name') }}" required>
            @error('business_name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-lg-6 mb-4">
            <input type="number" name="yearly_turnover" placeholder="Yearly Turnover" class="form-control @error('yearly_turnover') is-invalid @enderror" value="{{ old('yearly_turnover') }}">
            @error('yearly_turnover')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-lg-6 mb-4">
            <p class="txt-primary mb-3  poppins-medium">
              The service you are interested in
            </p>
            <label for="accounting" class="txt-primary  poppins-medium">
              <input type="checkbox" name="interested_service[]" value="accounting" {{ in_array('accounting', old('interested_service', [])) ? 'checked' : '' }}> Accounting
            </label> <br>
            <label for="tax" class="txt-primary poppins-medium">
              <input type="checkbox" name="interested_service[]" value="tax" {{ in_array('tax', old('interested_service', [])) ? 'checked' : '' }}>  TAX 
            </label>
            @error('interested_service')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-lg-12 mb-4">
            <label for="" class="txt-primary mb-3  poppins-medium">
              Tell us what makes you awake at night
            </label>
            <textarea name="message" id="" class="form-control @error('message') is-invalid @enderror" style="height: 160px;" placeholder="Describe your topic" required>{{ old('message') }}</textarea>
            @error('message')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-lg-12 text-center">
            <button type="submit" class="btn  bg-primary text-light py-1 px-3">Submit</button>
          </div>
        </div>
      </form>
    </div>
    </div>
  </div>
</section>