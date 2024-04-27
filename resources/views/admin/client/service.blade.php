<form id="serviceForm">
  <div class="row">
    <div class="col-md-12">
      <div class="row mt-3">
          <div class="col-3 text-center">
              <h5 class="mb-3">Choose Service</h5>
              <div class="form-check">
                <select id="serviceDropdown" class="form-control mt-2 select2">
                    <option value="">Select Service</option>
                    @foreach($services as $service)
                        <option value="{{ $service->id }}" {{ isset($client->clientService->service_id) && $client->clientService->service_id == $service->id ? 'selected' : '' }}>
                            {{ $service->name }}
                        </option>
                    @endforeach
                </select>
              </div>
          </div>    
          <div class="col-3 text-center">
              <h5 class="mb-3">Choose Manager</h5>
              <div class="form-check">
                  <select id="managerDropdown" class="form-control mt-2 select2">
                    <option value="">Select Manager</option>
                    @foreach($managers as $manager)
                        <option value="{{ $manager->id }}" {{ isset($client->clientService->manager_id) && $client->clientService->manager_id == $manager->id ? 'selected' : '' }}>
                            {{ $manager->first_name }}
                        </option>
                    @endforeach
                </select>
              </div>
          </div>  
          <div class="col-3 text-center">
              <h5 class="mb-3">Choose Frequency</h5>
              <div class="form-check">
                  <select id="service_frequency" class="form-control mt-2 select2" name="service_frequency">
                      <option value="">Select Frequency</option>
                      <option {{ isset($client->clientService->service_frequency) && $client->clientService->service_frequency == 'Daily' ? 'selected' : '' }}>Daily</option>
                      <option {{ isset($client->clientService->service_frequency) && $client->clientService->service_frequency == 'Weekly' ? 'selected' : '' }}>Weekly</option>
                      <option {{ isset($client->clientService->service_frequency) && $client->clientService->service_frequency == 'Monthly' ? 'selected' : '' }}>Monthly</option>
                      <option {{ isset($client->clientService->service_frequency) && $client->clientService->service_frequency == 'Quarterly' ? 'selected' : '' }}>Quarterly</option> 
                      <option {{ isset($client->clientService->service_frequency) && $client->clientService->service_frequency == 'Yearly' ? 'selected' : '' }}>Yearly</option>
                  </select>
              </div>
          </div>   
          <div class="col-3 text-center">
              <h5 class="mb-3">Deadline</h5>
              <div class="form-check">
                  <input type="date" id="service_deadline" class="form-control mt-2" name="service_deadline" value="{{ isset($client->clientService->service_deadline) ? $client->clientService->service_deadline : '' }}">
              </div>
          </div>
      </div>
    </div>
  </div>
  <div class="row mt-4">
    <div class="col-12">
      <h5 class="p-2 bg-theme text-white mb-0 text-capitalize">Sub Services Details</h5>
      <div class="border-theme p-3 border-1">
      <table class="table mt-3">
          <thead>
              <tr>
                  <th>Sub Service Name</th>
                  <th>Deadline</th>
                  <th>Staff</th>
                  <th>Note</th>
              </tr>
          </thead>
          <tbody id="serviceDetailsTable">
           
            @if(isset($client->clientService->clientSubServices))
                @foreach($client->clientService->clientSubServices as $clientSubService)
                    <tr>
                        <td>{{ $clientSubService->subService->name }}</td>
                        <input type="hidden" name="sub_service_id[]" value="{{ $clientSubService->subService->id }}">

                        <td>
                            <input type="date" name="deadline" class="form-control" value="{{ $clientSubService->deadline }}">
                        </td>

                        <td>
                            <select class="form-control select2" name="staff_id">
                                <option value="">Select Staff</option>
                                @foreach($staffs as $staff)
                                    <option value="{{ $staff->id }}" {{ $clientSubService->staff_id == $staff->id ? 'selected' : '' }}>
                                        {{ $staff->first_name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>

                        <td>
                            <textarea name="note" rows="1" class="form-control">{{ $clientSubService->note }}</textarea>
                        </td>
                    </tr>
                @endforeach
            @endif

          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="row mt-3">
      <div class="col-lg-4 mx-auto text-center">
          @if(isset($client->clientService))
          <button id="service-updateButton" class="btn btn-sm bg-theme text-light btn-outline-dark">Update</button>
          @else
          <button id="service-saveButton" class="btn btn-sm bg-theme text-light btn-outline-dark">Save</button>
          @endif
      </div>
  </div>
</form>