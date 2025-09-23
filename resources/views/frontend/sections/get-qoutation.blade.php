<section class="py-5 bg-primary">
   <div class="container">
      <div class="row py-5">
         <div class="col-lg-12">
            <div class="text-center w-100  mx-auto">
               <div class=" px-5">
                  <h1 class="text-light poppins-bold">{{ $getQuotation->short_title }}</h1>
                  <h3 class="text-light poppins-bold">{{ $getQuotation->long_title }}</h3>
               </div>
               <p class="text-light w-75 text-center my-3 mx-auto">
                  {!! $getQuotation->long_description !!}
               </p>
            </div>

         </div>
      </div>
   </div>
</section>

<section class="py-5 bg-light" id="get-qoutation">
   <div class="container">
      <div class="row py-2 col-lg-10 mx-auto">
         <h4 class="txt-primary text-center text-capitalize poppins-bold">Please fill up the form below</h4>
         <div class="card p-4 pt-5 mt-4">
            <form id="contactForm">
               <div class="row">
                  <div class="col-lg-6 mb-4">
                     <label for="name" class="txt-primary">Name<span class="text-danger">*</span></label>
                     <input type="text" name="name" id="name" class="form-control" value="{{ Auth::check() ? Auth::user()->first_name . ' ' . Auth::user()->last_name : '' }}">
                  </div>
                  <div class="col-lg-6 mb-4">
                     <label for="vat_returns" class="txt-primary">Do you want to complete your VAT Returns?<span class="text-danger">*</span></label>
                     <select name="vat_returns" id="vat_returns" class="form-control" style="appearance: auto;">
                        <option value="No">No</option>
                        <option value="Yes">Yes</option>
                     </select>
                  </div>
                  <div class="col-lg-6 mb-4">
                     <label for="email" class="txt-primary">Email<span class="text-danger">*</span></label>
                     <input type="email" name="email" id="email" class="form-control" value="{{ Auth::check() ? Auth::user()->email : '' }}">
                  </div>
                  <div class="col-lg-6 mb-4">
                     <label for="payroll" class="txt-primary">Do you want to manage Payroll?<span class="text-danger">*</span></label>
                     <select name="payroll" id="payroll" class="form-control" style="appearance: auto;">
                        <option value="No">No</option>
                        <option value="Yes">Yes</option>
                     </select>
                  </div>
                  <div class="col-lg-6 mb-4">
                     <label for="company_name" class="txt-primary">Company Name<span class="text-danger">*</span></label>
                     <input type="text" id="company_name" name="company_name" class="form-control">
                  </div>
                  <div class="col-lg-6 mb-4">
                     <label for="bookkeeping" class="txt-primary">Do you want us to complete bookkeeping?<span class="text-danger">*</span></label>
                     <select name="bookkeeping" id="bookkeeping" class="form-control" style="appearance: auto;">
                        <option value="No">No</option>
                        <option value="Yes">Yes</option>
                     </select>
                  </div>
                  <div class="col-lg-6 mb-4">
                     <label for="phone" class="txt-primary">Telephone No<span class="text-danger">*</span></label>
                     <input type="number" id="phone" name="phone" class="form-control" value="{{ Auth::check() ? Auth::user()->phone : '' }}">
                  </div>
                  <div class="col-lg-6 mb-4">
                     <label for="bookkeeping_software" class="txt-primary">Which Book Keeping software do you use?<span class="text-danger">*</span></label>
                     <input type="text" name="bookkeeping_software" id="bookkeeping_software" class="form-control" />
                  </div>
                  <div class="col-lg-6 mb-4">
                     <label for="business_type" class="txt-primary">Business Type<span class="text-danger">*</span></label>
                     <input type="text" id="business_type" name="business_type" class="form-control">
                  </div>
                  <div class="col-lg-6 mb-4">
                     <label for="management_account" class="txt-primary">Do you need Management Account?<span class="text-danger">*</span></label>
                     <select name="management_account" id="management_account" class="form-control" style="appearance: auto;">
                        <option value="Yes">Yes</option>
                        <option value="No">No</option>
                     </select>
                  </div>
                  <div class="col-lg-6 mb-4">
                     <label for="turnover" class="txt-primary">Your Turnover<span class="text-danger">*</span></label>
                     <input type="number" id="turnover" name="turnover" class="form-control">
                  </div>
                  <div class="col-lg-6 mb-4">
                     <label for="bank_accounts" class="txt-primary">How many bank accounts do you have?<span class="text-danger">*</span></label>
                     <input type="number" id="bank_accounts" name="bank_accounts" class="form-control">
                  </div>

                  <div id="loader2" class="text-center" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                           <span class="visually-hidden">Loading...</span>
                        </div>
                  </div>

                  <div class="col-lg-12 text-center my-4">
                     <button type="button" id="submitForm" class="btn bg-primary text-light py-1 px-3">Submit</button>
                  </div>
               </div>
            </form>
         </div>
      </div>
   </div>
</section>

<script>
   document.addEventListener('DOMContentLoaded', function() {
      document.getElementById('submitForm').addEventListener('click', function(e) {
         e.preventDefault();

         var name = document.getElementById('name').value.trim();
         var email = document.getElementById('email').value.trim();
         var company_name = document.getElementById('company_name').value.trim();
         var phone = document.getElementById('phone').value.trim();
         var business_type = document.getElementById('business_type').value.trim();
         var turnover = document.getElementById('turnover').value.trim();
         var vat_returns = document.getElementById('vat_returns').value;
         var payroll = document.getElementById('payroll').value;
         var bookkeeping = document.getElementById('bookkeeping').value;
         var bookkeeping_software = document.getElementById('bookkeeping_software').value;
         var management_account = document.getElementById('management_account').value;
         var bank_accounts = document.getElementById('bank_accounts').value.trim();

         if (!name || !email || !company_name || !phone || !business_type || !turnover || !vat_returns || !payroll || !bookkeeping || !bookkeeping_software || !management_account || !bank_accounts) {
            toastr.error('Please fill out all required fields.');
            return;
         }

         var formData = new FormData(document.getElementById('contactForm'));
         var csrfToken = '{{ csrf_token() }}';

         $.ajax({
            url: '{{ route('quotations.store') }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
               'X-CSRF-TOKEN': csrfToken
            },
            beforeSend: function() {
               document.getElementById('loader2').style.display = 'block';
               document.getElementById('submitForm').disabled = true;
            },
            success: function(data) {
               if (data.success) {
                  document.getElementById('loader2').style.display = 'none';
                  document.getElementById('submitForm').disabled = false;
                  toastr.success('Submitted successfully!');
                  document.getElementById('contactForm').reset();
               } else {
                  document.getElementById('loader2').style.display = 'none';
                  document.getElementById('submitForm').disabled = false;
                  toastr.error('An error occurred. Please try again.');
               }
            },
            error: function(xhr) {
                document.getElementById('loader2').style.display = 'none';
                document.getElementById('submitForm').disabled = false;

                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    let firstField = Object.keys(errors)[0];
                    toastr.error(errors[firstField][0]);
                } else {
                    toastr.error('An unexpected error occurred.');
                    console.error(xhr.responseText);
                }
            }
         });
      });
   });
</script>