<form id="contactForm">
    <div class="row">
        <div class="col-lg-4">
            <div class="row my-3">
                <input type="hidden" id="contactIdInput" name="contact_id">
                <div class="col-lg-12">
                    <label for="">Greeting</label>
                    <div class="mt-2">
                        <select class="form-control my-2" name="greeting" id="greeting">
                            <option value="" selected disabled>Choose greeting</option>
                            <option value="Mr."> Mr. </option>
                            <option value="Ms."> Ms. </option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-12">
                    <label for="">First Name</label>
                    <input type="text" class="form-control my-2" id="first_name" name="first_name" placeholder="Enter first name">
                </div>
                <div class="col-lg-12">
                    <label for="">Last Name</label>
                    <input type="text" class="form-control my-2" id="last_name" name="last_name" placeholder="Enter last name">
                </div>
                <div class="col-lg-12">
                    <label for="">Job Title</label>
                    <input type="text" class="form-control my-2" id="job_title" name="job_title" placeholder="Enter job title">
                </div>
                <div class="col-lg-12">
                    <label for="">Email</label>
                    <input type="email" class="form-control my-2" id="contact-email" name="email" placeholder="Enter email">
                </div>
                <div class="col-lg-12">
                    <label for="">Phone</label>
                    <input type="number" class="form-control my-2" id="contact-phone" name="phone" placeholder="Enter phone number">
                </div> 
            </div>
            <div class="col-12 mx-auto text-center">  
                <button id="contact-cancelButton" class="btn btn-sm btn-outline-dark" type="button" style="display: none;">Cancel</button>
                <button id="contact-updateButton" class="btn btn-sm bg-theme text-light btn-outline-dark" type="button" style="display: none;">Update</button>
                <button id="contact-clearButton" class="btn btn-sm btn-outline-dark">Clear</button>
                <button id="contact-saveButton" class="btn btn-sm bg-theme text-light btn-outline-dark">Save</button>
            </div>
        </div>
        <div class="col-lg-8">
            @if(isset($client->contactInfos))
            <p class="p-2 mt-3 bg-theme text-white px-3  mb-0 text-capitalize d-flex align-items-center">
                Contact List
            </p>
            <div class="border-theme p-3 border-1">
                <table id="contactTable" class="table ">
                    <thead>
                        <tr>
                            <th>Greeting</th>
                            <th>Name</th>
                            <th>Job Title</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($client->contactInfos as $contactInfo)
                            <tr data-contact-id="{{ $contactInfo->id }}"
                            data-client-id="{{ $contactInfo->client_id }}"
                            data-director-info='@json($contactInfo)'>
                                <td>{{ $contactInfo->greeting }}</td>
                                <td>{{ $contactInfo->first_name }}</td>
                                <td>{{ $contactInfo->job_title }}</td> 
                                <td>{{ $contactInfo->phone }}</td> 
                                <td>{{ $contactInfo->email }}</td> 
                                <td>
                                    <a type="button" class="fa fa-edit edit-contact" style="font-size: 20px;">
                                    </a>
                                    <a type="button" class="fas fa-trash delete-contact" style="color: red; font-size: 20px;">
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</form>
