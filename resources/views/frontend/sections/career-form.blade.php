<section class="py-5 bg-light">
  <div class="container">
    <div class="row py-2 col-lg-10 mx-auto">
      <h4 class="txt-primary text-center poppins-medium text-capitalize">Please fill up the information</h4>
      <div class="card p-4 pt-5 mt-4">
        <div class="row">
          <div class="col-lg-6 mb-4">
            <input type="text" id="name" name="name" placeholder="Name" class="form-control">
          </div>
          <div class="col-lg-6 mb-4">
            <input type="email" id="email" name="email" placeholder="Email" class="form-control">
          </div>
          <div class="col-lg-6 mb-4">
            <input type="number" id="phone" name="phone" placeholder="Phone" class="form-control">
          </div>
          <div class="col-lg-6 mb-4">
            <input type="url" id="linkedin_profile" name="linkedin_profile" placeholder="Linkedin Profile Link" class="form-control">
          </div>
          <div class="col-lg-6 mb-4">
            <input type="number" id="yearly_turnover" name="yearly_turnover" placeholder="Yearly Turnover" class="form-control">
          </div>
          <div class="col-lg-6 mb-4">
              <label for="cv" class="txt-primary mb-3 poppins-medium">
                  Upload Your Updated CV
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
           <textarea id="about_yourself" name="about_yourself" class="form-control " style="height: 160px;" placeholder="Describe about yourself"></textarea>
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
    let yearly_turnover = document.getElementById('yearly_turnover').value;
    let cv = document.getElementById('cv').files[0];
    let about_yourself = document.getElementById('about_yourself').value;

    if (!name || !email || !phone || !linkedin_profile || !yearly_turnover || !cv || !about_yourself) {
      swal({
        icon: 'warning',
        title: 'Validation Error',
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
    formData.append('yearly_turnover', yearly_turnover);
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
      contentType: false,
      processData: false,
      success: function(response) {
        swal({
          icon: 'success',
          title: 'Success',
          text: 'Career form submitted successfully!',
          button: 'OK'
        });

        document.getElementById('name').value = '';
        document.getElementById('email').value = '';
        document.getElementById('phone').value = '';
        document.getElementById('linkedin_profile').value = '';
        document.getElementById('yearly_turnover').value = '';
        document.getElementById('cv').value = '';
        document.getElementById('about_yourself').value = '';
        document.getElementById('file-preview').innerHTML = '';
      },
      error: function(xhr, status, error) {
              console.error(xhr.responseText);
        }
    });
  });
</script>