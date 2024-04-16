<form id="serviceForm">
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
    </div>

    <div class="row my-4">
        <div class="col-lg-12">
            <button id="addServiceButton" class="btn btn-sm bg-theme text-light btn-outline-dark">+ Add Service</button> <br><br>
                <div class="services-container">
                    <!-- Services will be dynamically added here -->
                </div>
        </div>
    </div>

    <!-- Specific Service -->
    <div class="row my-4">
        <div class="col-lg-6 px-0 border shadow-sm">
            <p class="p-2 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center">
                Specific service
            </p>
        </div>
    </div>
    <div class="row my-4 px-3">
        <div class="col-lg-6">
            <label for="">Type the task</label>
            <input for="" type="text" class="form-control mt-2" name="name" id="new_service_name">
        </div>
    </div>
    <!-- Specific Service -->
    
    <div class="row">
        <div class="col-lg-4 mx-auto text-center">
            <button id="service-clearButton" class="btn btn-sm btn-outline-dark mr-2">Clear</button>
            <button id="service-saveButton" class="btn btn-sm bg-theme text-light btn-outline-dark">Save</button>
        </div>
    </div>
</form>