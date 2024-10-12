<section class="py-5 bg-light">
  <div class="container">
    <div class="row py-2 col-lg-10 mx-auto">
      <h4 class="txt-primary text-center poppins-medium text-capitalize">Please fill up the information</h4>
      <div class="card p-4 pt-5 mt-4">
        <div class="row">
          <div class="col-lg-6 mb-4">
            <input type="text" id="name" name="name" placeholder="Name *" class="form-control required">
          </div>
          <div class="col-lg-6 mb-4">
            <input type="email" id="email" name="email" placeholder="Email *" class="form-control" required>
          </div>
          <div class="col-lg-6 mb-4">
            <input type="number" id="phone" name="phone" placeholder="Phone *" class="form-control" >
          </div>
          <div class="col-lg-6 mb-4">
            <input type="url" id="linkedin_profile" name="linkedin_profile" placeholder="Linkedin Profile Link *" class="form-control" required>
          </div>
          <div class="col-lg-12 mb-4">
              <label for="cv" class="txt-primary mb-3 poppins-medium">
                  Upload Your Updated CV * (Only .pdf, .docx, .doc, and max 5 MB)
              </label>
              <div class="position-relative">
                  <input type="file" id="cv" name="cv" class="form-control opacity-0" accept=".pdf, .docx, .doc">
                  <a style="pointer-events: none;" class="btn btn-theme-outline d-inline w-50 mx-auto rounded-3 fs-6 position-absolute top-0">Upload File</a>
              </div>
              <div id="file-preview" class="mt-3 text-primary"></div>
          </div>
          <div class="col-lg-12 mb-4">
            <label for="" class="txt-primary mb-3  poppins-medium">
            Tell us About Yourself
            </label>
           <textarea id="about_yourself" name="about_yourself" class="form-control " style="height: 160px;" placeholder="Describe about yourself *"></textarea>
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

            <div id="loader3" class="text-center" style="display: none;">
                  <div class="spinner-border text-primary" role="status">
                      <span class="visually-hidden">Loading...</span>
                  </div>
            </div>

          <div class="col-lg-12 text-center">
           <button id="submitBtn" type="submit" class="btn  bg-primary text-light py-1 px-3">Submit</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
  document.addEventListener('DOMContentLoaded', function () {
      const privacyPolicyCheckbox = document.getElementById('privacy_policy_checkbox');
      const captchaContainer = document.getElementById('captcha-container');
      const submitButton = document.getElementById('submitBtn');

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
      const submitButton = document.getElementById('submitBtn');
      submitButton.disabled = false;
  }
</script>

<script>

  document.getElementById('cv').addEventListener('change', function(event) {
        const file = event.target.files[0];
        const previewDiv = document.getElementById('file-preview');
        
        if (file) {
            previewDiv.innerHTML = `<strong>Selected File:</strong> ${file.name}`;
        } else {
            previewDiv.innerHTML = '';
        }
  });

  document.getElementById('submitBtn').addEventListener('click', function(event) {
    event.preventDefault();
    let name = document.getElementById('name').value;
    let email = document.getElementById('email').value;
    let phone = document.getElementById('phone').value;
    let linkedin_profile = document.getElementById('linkedin_profile').value;
    let cv = document.getElementById('cv').files[0];
    let about_yourself = document.getElementById('about_yourself').value;

    if (!name || !email || !phone || !linkedin_profile || !cv || !about_yourself) {
      swal({
        icon: 'warning',
        title: 'Error',
        text: 'Please fill out all required fields.',
        button: 'OK'
      });
      return;
    }

    let formData = new FormData();
    formData.append('name', name);
    formData.append('email', email);
    formData.append('phone', phone);
    formData.append('linkedin_profile', linkedin_profile);
    formData.append('cv', cv);
    formData.append('about_yourself', about_yourself);

    var csrfToken = '{{ csrf_token() }}';

    $.ajax({
      url: "{{ route('career.store') }}",
      type: 'POST',
      data: formData,
      headers: {
        'X-CSRF-TOKEN': csrfToken
      },

      beforeSend: function() {
          document.getElementById('loader3').style.display = 'block';
          document.getElementById('submitBtn').disabled = true;
      },
      contentType: false,
      processData: false,
      success: function(response) {
        document.getElementById('loader3').style.display = 'none';
        document.getElementById('submitBtn').disabled = false;
        swal({
          icon: 'success',
          title: 'Success',
          text: 'Submitted successfully!',
          button: 'OK'
        });

        document.getElementById('name').value = '';
        document.getElementById('email').value = '';
        document.getElementById('phone').value = '';
        document.getElementById('linkedin_profile').value = '';
        document.getElementById('cv').value = '';
        document.getElementById('about_yourself').value = '';
        document.getElementById('file-preview').innerHTML = '';
      },
      error: function(xhr, status, error) {
          document.getElementById('loader3').style.display = 'none';
          document.getElementById('submitBtn').disabled = false;
              // console.error(xhr.responseText);

            swal({
                  icon: 'error',
                  title: 'Error',
                  text: 'An error occurred. Please try again.',
                  button: 'OK'
              });
        }
    });
  });
</script>