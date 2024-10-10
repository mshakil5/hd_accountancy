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
            <input type="text" name="name" placeholder="Name*" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') ?? (Auth::check() ? Auth::user()->first_name . ' ' . Auth::user()->last_name : '') }}" required>
            @error('name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-lg-6 mb-4">
            <input type="email" name="email" placeholder="Email*" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') ?? (Auth::check() ? Auth::user()->email : '') }}" required>
            @error('email')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-lg-6 mb-4">
            <input type="number" name="phone" placeholder="Phone*" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') ?? (Auth::check() ? Auth::user()->phone : '') }}" required>
            @error('phone')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-lg-6 mb-4">
            <input type="text" name="business_name" placeholder="Business Name" class="form-control @error('business_name') is-invalid @enderror" value="{{ old('business_name') }}">
            @error('business_name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-lg-6 mb-4">
              <select name="yearly_turnover" class="form-control @error('yearly_turnover') is-invalid @enderror">
                  <option value="">Select Yearly Turnover</option>
                  <option value="₤1 - ₤50K">₤1 - ₤50K</option>
                  <option value="₤51K - ₤90K">₤51K - ₤90K</option>
                  <option value="₤91K - ₤150K">₤91K - ₤150K</option>
                  <option value="₤151K - ₤250K">₤151K - ₤250K</option>
                  <option value="Over ₤251K">Over ₤251K</option>
                  {{--  
                  @foreach($turnoverRanges as $range)
                      <option value="{{ $range->price_range }}" {{ old('yearly_turnover') == $range->price_range ? 'selected' : '' }}>
                          {{ $range->price_range }}
                      </option>
                  @endforeach
                  --}}
              </select>
              @error('yearly_turnover')
                  <div class="invalid-feedback">{{ $message }}</div>
              @enderror
          </div>

          <div class="col-lg-6 mb-4">
            <p class="txt-primary mb-3 poppins-medium">
                The service you are interested in
            </p>
            <label for="accounting" class="txt-primary poppins-medium">
                <input type="checkbox" name="interested_service[]" value="Accounting"> Accounting
            </label> <br>
            <label for="tax" class="txt-primary poppins-medium">
                <input type="checkbox" name="interested_service[]" value="Tax"> TAX 
            </label> <br>
            <label for="digital_bookkeeping" class="txt-primary poppins-medium">
                <input type="checkbox" name="interested_service[]" value="Digital Bookkeeping Service"> Digital Bookkeeping Service 
            </label> <br>
            <label for="cloud_accounting" class="txt-primary poppins-medium">
                <input type="checkbox" name="interested_service[]" value="Cloud Accounting"> Cloud Accounting 
            </label> <br>
            <label for="payroll_service" class="txt-primary poppins-medium">
                <input type="checkbox" name="interested_service[]" value="Payroll Service"> Payroll Service 
            </label> <br>
            <label for="management_account" class="txt-primary poppins-medium">
                <input type="checkbox" name="interested_service[]" value="Management Account"> Management Account 
            </label> <br>
            <label for="year_end_account" class="txt-primary poppins-medium">
                <input type="checkbox" name="interested_service[]" value="Year End Account"> Year End Account 
            </label>
            @if ($errors->has('interested_service'))
                <div class="invalid-feedback d-block">
                    {{ $errors->first('interested_service') }}
                </div>
            @endif
        </div>

          <div class="col-lg-12 mb-4">
            <label for="" class="txt-primary mb-3  poppins-medium">
              Tell us what makes you awake at night
            </label>
            <textarea name="message" id="" class="form-control @error('message') is-invalid @enderror" style="height: 160px;" placeholder="Describe your topic *" required>{{ old('message') }}</textarea>
            @error('message')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-lg-12 mb-4">
            <label for="privacy_policy" class="txt-primary poppins-medium">
                <input type="checkbox" id="privacy_policy_checkbox" required> I agree to the 
                <a href="{{ route('frontend.privacyPolicy') }}" target="_blank">Privacy Policy</a>
            </label>
          </div>

          <div class="col-lg-12 mb-4" id="captcha-container" style="display: none;">
              <div class="g-recaptcha" 
                  data-sitekey="6Lf0hUwqAAAAADTHYCLkkMqbIuxcW8GcMEKWW7mQ"
                  data-callback="onCaptchaSuccess">
              </div>
          </div>

          <div class="col-lg-12 text-center">
            <button type="submit" id="submit_button" class="btn bg-primary text-light py-1 px-3" disabled>Submit</button>
          </div>
        </div>
      </form>
    </div>
    </div>
  </div>
</section>

<script>
  document.addEventListener('DOMContentLoaded', function () {
      const privacyPolicyCheckbox = document.getElementById('privacy_policy_checkbox');
      const captchaContainer = document.getElementById('captcha-container');
      const submitButton = document.getElementById('submit_button');

      submitButton.disabled = true;

      privacyPolicyCheckbox.addEventListener('change', function () {
          if (privacyPolicyCheckbox.checked) {
              captchaContainer.style.display = 'block';
          } else {
              captchaContainer.style.display = 'none';
          }
      });
  });

  function onCaptchaSuccess() {
      const submitButton = document.getElementById('submit_button');
      submitButton.disabled = false;
  }
</script>