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
            <select id="managerDropdown" class="form-control mt-2 select2">
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

          </tbody>
      </table>
      </div>
    </div>
  </div>

  <div class="row mt-3">
      <div class="col-lg-4 mx-auto text-center">
          <button id="service-saveButton" class="btn btn-sm bg-theme text-light btn-outline-dark">Save</button>
          <button id="service-updateButton" class="btn btn-sm bg-theme text-light btn-outline-dark">Update</button>
          <button id="service-clearButton" class="btn btn-sm btn-outline-dark">Clear</button>         
      </div>
  </div>
</form>
