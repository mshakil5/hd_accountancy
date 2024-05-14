<form id="serviceForm">
  <div class="row">
    <div class="col-md-12">
      <div class="row mt-3">
        <div class="col-3">
          <div class="form-check">
            <h5 class="mb-2">Choose Service</h5>
            <select id="serviceDropdown" class="form-control mt-1 select2" style="width:100%">
              <option value="" selected >Select Service</option>
              @foreach($services as $service)
              <option value="{{ $service->id }}" {{ isset($client->clientService->service_id) && $client->clientService->service_id == $service->id? 'selected' : '' }}>
                {{ $service->name }}
              </option>
              @endforeach
            </select>
          </div>
        </div>    
      </div>
    </div>
  </div>

    @if(isset($client->clientServices))
        @foreach($client->clientServices as $clientService)
            <div class="row mt-4 subServiceDetails">
                <div class="col-12">
                    <h5 class="p-2 bg-theme text-white mb-0 text-capitalize">Services Details</h5>
                    <div class="border-theme p-3 border-1">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-2">
                                        <h5 class="mb-3">Service Name</h5>
                                        <p><b>{{ isset($clientService->service->name) ? $clientService->service->name : '' }}</b></p>
                                        <input type="hidden" name="service_id" value="{{ optional($clientService->service)->id }}">
                                        <input type="hidden" name="client_service_id[]" value="{{ optional($clientService)->id }}">
                                        
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <h5 class="mb-3">Manager</h5>
                                        <div class="form-check">
                                            <select class="form-control mt-2 select2 managerDropdown" name="manager_id" style="width:100%">
                                                <option value="">Select</option>
                                                @foreach($managers as $manager)
                                                    <option value="{{ $manager->id }}" {{ isset($clientService) && $clientService->manager_id == $manager->id ? 'selected' : '' }}>
                                                        {{ $manager->first_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <h5 class="mb-3">Frequency</h5>
                                        <div class="form-check">
                                            <select id="serviceFrequency" class="form-control serviceFrequency" name="service_frequency">
                                                <option value="">Select Frequency</option>
                                                <option {{ isset($clientService) && $clientService->service_frequency == 'Daily' ? 'selected' : '' }}>Daily</option>
                                                <option {{ isset($clientService) && $clientService->service_frequency == 'Weekly' ? 'selected' : '' }}>Weekly</option>
                                                <option {{ isset($clientService) && $clientService->service_frequency == 'Monthly' ? 'selected' : '' }}>Monthly</option>
                                                <option {{ isset($clientService) && $clientService->service_frequency == 'Quarterly' ? 'selected' : '' }}>Quarterly</option> 
                                                <option {{ isset($clientService) && $clientService->service_frequency == 'Yearly' ? 'selected' : '' }}>Yearly</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <h5 class="mb-3">Deadline</h5>
                                        <div class="form-check">
                                            <input type="date" class="form-control serviceDeadline" name="service_deadline" id="serviceDeadline" value="{{ isset($clientService) ? $clientService->service_deadline : '' }}">
                                        </div>
                                    </div>
                                    <div class="col-md-1">
                                        <h5 class="mb-3">Action</h5>
                                        <span class="removeSubServiceDetails" style="cursor: pointer; font-size: 24px; color: red;">&#10006;</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <table class="table mt-3">
                            <thead>
                                <tr>
                                    <th>Sub Service Name</th>
                                    <th>Deadline</th>
                                    <th>Staff</th>
                                    <th>Note</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($clientService->clientSubServices as $clientSubService)
                                    <tr>
                                        <input type="hidden" name="client_sub_service_id[]" value="{{ $clientSubService->id }}">
                                         <input type="hidden" name="sub_service_id[]" value="{{ $clientSubService->subService->id }}">
                                        <td>{{ isset($clientSubService->subService->name) ? $clientSubService->subService->name : '' }}</td>
        
                                        <td>
                                            <input type="date" id="deadline" name="deadline" class="form-control" value="{{ isset($clientSubService->deadline) ? $clientSubService->deadline : '' }}">
                                        </td>
                                        <td>
                                            <select class="form-control select2 staffDropdown" id="selectedStaff"name="staff_id" style="width:100%">
                                                <option value="">Select Staff</option>
                                                @foreach($staffs as $staff)
                                                    <option value="{{ $staff->id }}" {{ isset($clientSubService->staff_id) && $clientSubService->staff_id == $staff->id ? 'selected' : '' }}>
                                                        {{ $staff->first_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <textarea name="note" id="note" rows="1" class="form-control" placeholder="Note for this task">{{ isset($clientSubService->note) ? $clientSubService->note : '' }}</textarea>
                                        </td>
                                        <td><span class="removeSubServiceRow" style="cursor: pointer; font-size: 24px; color: red;">&#10006;</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    @endif

</form>

<div class="row mt-3">
    @if(isset($client->clientServices) && count($client->clientServices) > 0)
        <div class="col-lg-4 mx-auto text-center">
            <button id="service-updateButton" class="btn btn-sm bg-theme text-light btn-outline-dark">Update</button>
        </div>
    @else
        <div class="col-lg-4 mx-auto text-center">
            <button id="service-saveButton" class="btn btn-sm bg-theme text-light btn-outline-dark">Save</button>
        </div>
    @endif
</div>