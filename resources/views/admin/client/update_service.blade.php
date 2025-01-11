<form id="serviceForm">
  <div class="row">
    <div class="col-md-12">
      <div class="row mt-3">
        <div class="col-3">
          <div class="form-check">
            <h5 class="mb-2">Choose Service <span class="text-danger">*</span></h5>
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
    @php
    use Carbon\Carbon;

    $currentDate = Carbon::now();
    @endphp

    @if(isset($client->clientServices))
        @foreach($client->clientServices as $clientService)

            @php
                $isRelevant = in_array($clientService->status, [0, 1]);
            @endphp

            <div class="row mt-4 subServiceDetails {{ !$isRelevant ? 'd-none' : '' }}">
                <div class="col-12">
                    <h5 class="p-2 bg-theme text-white mb-0 text-capitalize">Services Details</h5>
                    <div class="border-theme p-3 border-1">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-1 text-center">
                                        <h5 class="mb-3">Service</h5>
                                        <p><b>{{ isset($clientService->service->name) ? $clientService->service->name : '' }}</b></p>
                                        <input type="hidden" name="service_id" value="{{ optional($clientService->service)->id }}">
                                        <input type="hidden" name="client_service_id[]" value="{{ optional($clientService)->id }}">
                                    </div>
                                    <div class="col-md-2 text-center">
                                        <h5 class="mb-3">Manager</h5>
                                        <div class="form-check">
                                            <select class="form-control mt-2 managerDropdown" name="manager_id" style="width:100%">
                                                <option value="">Select</option>
                                                @foreach($managers as $manager)
                                                    <option value="{{ $manager->id }}" {{ isset($clientService) && $clientService->manager_id == $manager->id ? 'selected' : '' }}>
                                                        {{ $manager->first_name }} {{ $manager->last_name }} ({{ $manager->type }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2 text-center">
                                        <h5 class="mb-3">Frequency</h5>
                                        <div class="form-check">
                                            <select id="serviceFrequency" class="form-control serviceFrequency" name="service_frequency">
                                                <option value="">Select Frequency</option>
                                                <option {{ isset($clientService) && $clientService->service_frequency == 'Weekly' ? 'selected' : '' }}>Weekly</option>
                                                <option {{ isset($clientService) && $clientService->service_frequency == '2 Weekly' ? 'selected' : '' }}>2 Weekly</option>
                                                <option {{ isset($clientService) && $clientService->service_frequency == '4 Weekly' ? 'selected' : '' }}>4 Weekly</option>
                                                <option {{ isset($clientService) && $clientService->service_frequency == 'Monthly' ? 'selected' : '' }}>Monthly</option>
                                                <option {{ isset($clientService) && $clientService->service_frequency == 'Quarterly' ? 'selected' : '' }}>Quarterly</option>
                                                <option {{ isset($clientService) && $clientService->service_frequency == 'Annually' ? 'selected' : '' }}>Annually</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2 text-center">
                                        <h5 class="mb-3">Due Date</h5>
                                        <div class="form-check">
                                            <input type="text" class="form-control dueDate" name="dueDate" id="dueDate" value="{{ \Carbon\Carbon::parse($clientService->due_date)->format('d-m-Y') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-2 text-center">
                                        <h5 class="mb-3">Target Deadline</h5>
                                        <div class="form-check">
                                            <input type="text" class="form-control legalDeadline" name="legalDeadline" id="legalDeadline" value="{{ \Carbon\Carbon::parse($clientService->legal_deadline)->format('d-m-Y') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-2 text-center">
                                        <h5 class="mb-3">Deadline</h5>
                                        <div class="form-check">
                                            <input type="text" class="form-control serviceDeadline" name="service_deadline" id="serviceDeadline" value="{{ \Carbon\Carbon::parse($clientService->service_deadline)->format('d-m-Y') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-1 text-center">
                                        <h5 class="mb-3">Action</h5>
                                        <span class="removeSubServiceDetails" style="cursor: pointer; font-size: 24px; color: red; margin-right: 5px;">&#10006;</span>
                                        @if($clientService->is_next_date_added == 0)
                                            <i id="continuousSwitch{{ $clientService->id }}" class="fas fa-sync" 
                                                style="cursor: pointer; font-size: 24px; color: {{ isset($clientService->continuous) && $clientService->continuous == 1 ? '#28a745' : '#dc3545' }};" 
                                                onclick="toggleContinuous({{ $clientService->id }}, this)">
                                            </i>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <table class="table mt-3">
                            <thead>
                                <tr>
                                    <th>Sub Service</th>
                                    <th>Deadline</th>
                                    <th>Staff</th>
                                    <th>Note</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($clientService->clientSubServices as $clientSubService)
                                    <tr>
                                        <input type="hidden" name="client_sub_service_id[]" value="{{ $clientSubService->id }}">
                                        <input type="hidden" name="sub_service_id[]" value="{{ $clientSubService->subService->id }}">
                                        <td>{{ isset($clientSubService->subService->name) ? $clientSubService->subService->name : '' }}</td>
                                        <td>
                                            <input type="text" id="deadline" name="deadline" class="form-control subServiceDeadline" value="{{ isset($clientSubService->deadline) ? $clientSubService->deadline : '' }}">
                                        </td>
                                        <td>
                                            <select class="form-control staffDropdown" id="selectedStaff" name="staff_id" style="width:100%">
                                                <option value="">Select Staff</option>
                                                @foreach($staffs as $staff)
                                                    <option value="{{ $staff->id }}" {{ isset($clientSubService->staff_id) && $clientSubService->staff_id == $staff->id ? 'selected' : '' }}>
                                                        {{ $staff->first_name }} {{ $staff->last_name }} ({{ $staff->type }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <textarea name="note" id="note" rows="1" class="form-control" placeholder="Note for this task">{{ isset($clientSubService->note) ? $clientSubService->note : '' }}</textarea>
                                        </td>
                                        <td class="text-center"><span class="removeSubServiceRow" style="cursor: pointer; font-size: 24px; color: red;">&#10006;</span></td>
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

<script>
    function toggleContinuous(serviceId, iconElement) {
        iconElement.classList.add('fa-spin');

        const formData = new FormData();
        formData.append('id', serviceId);
        formData.append('_token', "{{ csrf_token() }}");

        fetch("{{ route('toggle.continuous') }}", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            iconElement.classList.remove('fa-spin');

            if (data.success) {
                toastr.success("Status changed!", "Success", {
                    timeOut: 3000,
                    closeButton: true,
                    progressBar: true
                });

                setTimeout(function() {
                    location.reload();
                }, 500);
            } else {
                toastr.error(data.message || "Failed to update status!", "Error", {
                    timeOut: 3000,
                    closeButton: true,
                    progressBar: true
                });
            }
        })
        .catch(error => {
            iconElement.classList.remove('fa-spin');
            toastr.error("An error occurred. Please try again.", "Error", {
                timeOut: 3000,
                closeButton: true,
                progressBar: true
            });
        });
    }
</script>