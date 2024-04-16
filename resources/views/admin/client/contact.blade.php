<form id="contactForm">
    <div class="row my-4">
        <div class="col-lg-4">
            <label for="">Client reference</label>
             <div class="mt-2">
                <select class="form-control my-2 select2" id="client_id" name="client_id">
                    <option value="" selected disabled>Choose client reference</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->refid }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-lg-4">
            <label for="">Greeting</label>
            <div class="mt-2">
                <select class="form-control my-2" name="greeting" id="greeting">
                    <option value="" selected disabled>Choose greeting</option>
                    <option value="Mr."> Mr. </option>
                    <option value="Ms."> Ms. </option>
               </select> 
            </div>
        </div>
        <div class="col-lg-4">
            <label for="">First Name</label>
            <input type="text" class="form-control my-2" id="first_name" name="first_name" placeholder="Enter first name">
        </div>
        <div class="col-lg-4">
            <label for="">Last Name</label>
            <input type="text" class="form-control my-2" id="last_name" name="last_name" placeholder="Enter email">
        </div>
        <div class="col-lg-4">
            <label for="">Job Title</label>
            <input type="text" class="form-control my-2" id="job_title" name="job_title" placeholder="Enter job title">
        </div>
        <div class="col-lg-4">
            <label for="">Email</label>
            <input type="email" class="form-control my-2" id="email" name="email" placeholder="Enter email">
        </div>
        <div class="col-lg-4">
            <label for="">Phone</label>
            <input type="number" class="form-control my-2" id="phone" name="phone" placeholder="Enter phone number">
        </div> 
    </div>
    <div class="row">
        <div class="col-lg-4 mx-auto text-center">
            <button id="contact-clearButton" class="btn btn-sm btn-outline-dark">Clear</button>
            <button id="contact-saveButton" class="btn btn-sm bg-theme text-light btn-outline-dark">Save</button>
        </div>
    </div>
</form>