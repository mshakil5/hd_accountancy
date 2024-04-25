<!-- <form id="serviceForm">
    <div class="row my-4">
        <div class="col-lg-12">
                <div class="services-container"> -->
                    <!-- Services will be dynamically added here -->
                <!-- </div>   
        </div>  
    </div>
    <div class="row my-4 px-3">
        <div class="col-lg-4">
            <label for="deadline" class="mb-2">Deadline</label>
            <input type="date" id="deadline" name="deadline" class="form-control">
        </div>
    </div> -->

    <!-- Specific Service -->
    <!-- <div class="row my-4">
        <div class="col-lg-6 px-0 border shadow-sm">
            <p class="p-2 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center">
                Specific service
            </p>
        </div>
    </div>
<div class="row my-4 px-3">
    <div class="col-lg-6 text-center">
        <label for="">Type the service</label>
        <input for="" type="text" class="form-control mt-2" name="name" id="new_service_name">
        <button id="addServiceButton" class="btn btn-sm bg-theme text-light btn-outline-dark mt-3">+ Add Service</button>
    </div>
</div> -->

    <!-- Specific Service -->
    
    <!-- <div class="row">
        <div class="col-lg-4 mx-auto text-center">
             @if(isset($client->clientService))
             <button id="service-saveButton" class="btn btn-sm bg-theme text-light btn-outline-dark">Update</button>
             @else
            <button id="service-clearButton" class="btn btn-sm btn-outline-dark mr-2">Clear</button>
            <button id="service-saveButton" class="btn btn-sm bg-theme text-light btn-outline-dark">Save</button>
            @endif
        </div>
    </div>
</form> -->

<!-- New design start  -->

<form id="serviceForm">
  <div class="row">
    <div class="col-md-6">
      <div class="row mt-3">
        <div class="col-6">
          <h5 class="mb-3">Choose Service</h5>
          <div class="form-check">
            <select id="serviceDropdown" class="form-control mt-2 select2">
                <option value="">Select Service</option>
                @foreach($services as $service)
                    <option value="{{ $service->id }}">{{ $service->name }}</option>
                @endforeach
            </select>
          </div>
        </div>
        <div class="col-6">
          <h5 class="mb-3">Choose Manager</h5>
          <div class="form-check">
            <select id="" class="form-control mt-2 select2">
                <option value="">Select Manager</option>
                @foreach($managers as $manager)
                    <option value="{{ $manager->id }}">{{ $manager->first_name }}</option>
                @endforeach
            </select>
          </div>
        </div>
      </div>
    </div>
    <!-- <div class="col-md-6">
      <div class="row mt-3">
        <div class="col-12">
          <h5 class="mb-3">Add Specific Service</h5>
          <div class="input-group mb-3">
            <input type="text" class="form-control" placeholder="Enter specific service">
            <button class="btn btn-sm bg-theme text-light btn-outline-dark" type="button">Add</button>
          </div>
        </div>
      </div>
    </div> -->
  </div>
  <div class="row mt-4">
    <div class="col-12">
      <h5 class="p-2 bg-theme text-white mb-0 text-capitalize">Service Details</h5>
      <div class="border-theme p-3 border-1">
      <table class="table mt-3">
          <thead>
              <tr>
                  <th>Sub Service Name</th>
                  <th>Frequency</th>
                  <th>Deadline</th>
                  <th>Note</th>
                  <th>Staff</th>
              </tr>
          </thead>
          <tbody id="serviceDetailsTable">
              <!-- Initial row for the first service -->
          </tbody>
      </table>
      </div>
    </div>
  </div>

  <div class="row mt-3">
      <div class="col-lg-4 mx-auto text-center">
          <button id="service-updateButton" class="btn btn-sm bg-theme text-light btn-outline-dark">Update</button>
          <button id="service-clearButton" class="btn btn-sm btn-outline-dark">Clear</button>
          <button id="service-saveButton" class="btn btn-sm bg-theme text-light btn-outline-dark">Save</button>
      </div>
  </div>
</form>

<!-- New design end  -->
