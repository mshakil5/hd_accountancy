<form id="directorForm">
    <div class="row">
        <div class="col-lg-4">
            <div class="row my-3">  
                <input type="hidden" id="directorIdInput" name="director_id">
                <div class="col-lg-12">
                    <label for="name">Director Name</label>
                    <input type="text" class="form-control my-2" id="dir-name" name="name" placeholder="Enter name">
                </div>
                <div class="col-lg-12">
                    <label for="phone">Director Phone</label>
                    <input type="number" class="form-control my-2" id="dir-phone" name="phone" placeholder="Enter phone number">
                </div>
                <div class="col-lg-12">
                    <label for="email">Director Email</label>
                    <input type="email" class="form-control my-2" id="dir-email" name="email" placeholder="Enter email">
                </div>
                <div class="col-lg-12">
                    <label for="address">Director Address</label>
                    <input type="text" class="form-control my-2" id="address" name="address" placeholder="Enter address">
                </div>
                <div class="col-lg-12">
                    <label for="dob">Date Of Birth</label>
                    <input type="date" class="form-control my-2" id="dob" name="dob" placeholder="Enter date of birth">
                </div>
                <div class="col-lg-12">
                    <label for="ni_number">NI Number</label>
                    <input type="text" class="form-control my-2" id="ni_number" name="ni_number" placeholder="Enter NI number">
                </div>
                <div class="col-lg-12">
                    <label for="utr_number">UTR Number</label>
                    <input type="number" class="form-control my-2" id="utr_number" name="utr_number" placeholder="Enter UTR number">
                </div>
                <div class="col-lg-12">
                    <label for="utr_authorization">UTR Authorization</label>
                    <input type="number" class="form-control my-2" id="utr_authorization" name="utr_authorization" placeholder="Enter UTR authorization">
                </div>
                <div class="col-lg-12">
                    <label for="nino">NINO</label>
                    <input type="number" class="form-control my-2" id="nino" name="nino" placeholder="Enter NINO number">
                </div>
            </div>
            <div class="col-lg-12 mx-auto text-center">
                <button id="director-cancelButton" class="btn btn-sm btn-outline-dark">Cancel</button>
                <button id="director-updateButton"  class="btn btn-sm bg-theme text-light btn-outline-dark">Update</button>
                <button id="director-saveButton" class="btn btn-sm bg-theme text-light btn-outline-dark">Save</button>
                <button id="director-clearButton"  class="btn btn-sm btn-outline-dark">Clear</button>
            </div>
        </div>

        <div class="col-lg-8">
            @if(isset($directorInfos))
            <div class="p-2 mt-3 bg-theme text-white px-3  mb-0 text-capitalize d-flex align-items-center">
                Director Info List
            </div>
            <div class="border-theme p-3 border-1">
                <table id="directorUpdateTable" class="table">
                    <thead>
                        <tr>
                            <th>Director Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>NI Number</th>
                            <th>UTR Number</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($directorInfos as $directorInfo)
                            <tr data-director-id="{{ $directorInfo->id }}" data-client-id="{{ $directorInfo->client_id }}" data-director-info='@json($directorInfo)'>
                                <td>{{ $directorInfo->name }}</td>
                                <td>{{ $directorInfo->phone }}</td>
                                <td>{{ $directorInfo->email }}</td> 
                                <td>{{ $directorInfo->ni_number }}</td> 
                                <td>{{ $directorInfo->utr_number }}</td> 
                                <td>
                                    <a type="button" class="fa fa-edit edit-director" style="font-size: 20px;"></a>
                                    <a type="button" class="fas fa-trash delete-director" style="color: red; font-size: 20px;"></a>
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