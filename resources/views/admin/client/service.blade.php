<form id="serviceForm">
    <div class="row my-4">
        <div class="col-lg-12">
                <div class="services-container">
                    <!-- Services will be dynamically added here -->
                </div>   
        </div>  
    </div>
    <div class="row my-4 px-3">
        <div class="col-lg-4">
            <label for="deadline" class="mb-2">Deadline</label>
            <input type="date" id="deadline" name="deadline" class="form-control">
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
    <div class="col-lg-6 text-center">
        <label for="">Type the service</label>
        <input for="" type="text" class="form-control mt-2" name="name" id="new_service_name">
        <button id="addServiceButton" class="btn btn-sm bg-theme text-light btn-outline-dark mt-3">+ Add Service</button>
    </div>
</div>

    <!-- Specific Service -->
    
    <div class="row">
        <div class="col-lg-4 mx-auto text-center">
             @if(isset($client->clientService))
             <button id="service-saveButton" class="btn btn-sm bg-theme text-light btn-outline-dark">Update</button>
             @else
            <button id="service-clearButton" class="btn btn-sm btn-outline-dark mr-2">Clear</button>
            <button id="service-saveButton" class="btn btn-sm bg-theme text-light btn-outline-dark">Save</button>
            @endif
        </div>
    </div>
</form>

<!-- New design start  -->

<!-- <div class="row">
  <div class="col-md-6">
    <div class="row mt-3">
      <div class="col-12">
        <h5 class="mb-3">Choose Service</h5>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="serviceOption1">
          <label class="form-check-label" for="serviceOption1">
            Service Option 1
          </label>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="row mt-3">
      <div class="col-12">
        <h5 class="mb-3">Add Specific Service</h5>
        <div class="input-group mb-3">
          <input type="text" class="form-control" placeholder="Enter specific service">
          <button class="btn btn-sm bg-theme text-light btn-outline-dark" type="button">Add</button>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="row mt-4">
  <div class="col-12">
    <h5 class="p-2 bg-theme text-white mb-0 text-capitalize">Service Details</h5>
    <div class="border-theme p-3 border-1">
      <table class="table mt-3">
        <thead>
          <tr>
            <th>Service Name</th>
            <th>Frequency</th>
            <th>Deadline</th>
            <th>Staff</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Service 1</td>
            <td>
                <select class="form-select">
                    <option selected>Daily</option>
                    <option>Weekly</option>
                    <option>Monthly</option>
                    <option>Yearly</option>
                </select>
                </td>
            <td><input type="date" class="form-control"></td>
            <td>
                <select class="form-select">
                    <option selected>John Doe</option>
                    <option>Jane Smith</option>
                    <option>Robert Johnson</option>
                  
                </select>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div> -->

<!-- New design end  -->
