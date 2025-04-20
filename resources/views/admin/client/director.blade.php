<div class="container-fluid">
    <div class="row">
        <div id="leftContainer" class="col-lg-3">
            <div class="row">
                <div class="col-12 text-right my-3">
                    <button id="createNewButton" class="btn bg-theme text-light btn-outline-dark">Create New</button>
                </div>
            </div>

            <div class="row" id="directorFormContainer" style="display: none;">
                <div class="col-12">
                    <form id="directorForm">
                        <input type="hidden" id="directorIdInput" name="director_id">
                        <div class="form-group">
                            <label for="name">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control my-2" id="dir-name" name="name" placeholder="Enter name">
                        </div>
                        <div class="form-group">
                            <label for="name">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control my-2" id="dir-last-name" name="last_name" placeholder="Enter last name">
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone <span class="text-danger">*</span></label>
                            <input type="number" class="form-control my-2" id="dir-phone" name="phone" placeholder="Enter phone number">
                        </div>
                        <div class="form-group">
                            <label for="email">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control my-2" id="dir-email" name="email" placeholder="Enter email">
                        </div>
                        <div class="form-group">
                            <label for="address">Address Line <span class="text-danger">*</span></label>
                            <input type="text" class="form-control my-2" id="address" name="address" placeholder="Enter address">
                        </div>
                        <div class="form-group">
                            <label for="dob">Date Of Birth <span class="text-danger">*</span></label>
                            <input type="date" class="form-control my-2" id="dob" name="dob" placeholder="Enter date of birth">
                        </div>
                        <div class="form-group">
                            <label for="ni_number">NI Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control my-2" id="ni_number" name="ni_number" placeholder="Enter NI number">
                        </div>
                        <div class="form-group">
                            <label for="directors_tax_return">Director's Tax Return</label>
                            <select class="form-control my-2" id="directors_tax_return" name="directors_tax_return">
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="hmrc_authorisation">HMRC Authorisation</label>
                            <select class="form-control my-2" id="hmrc_authorisation" name="hmrc_authorisation">
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="utr_number">UTR Number</label>
                            <input type="number" class="form-control my-2" id="utr_number" name="utr_number" placeholder="Enter UTR number">
                        </div>
                        <div class="form-group">
                            <label for="utr_authorization">DIR UTR Authorization</label>
                            <select class="form-control my-2" id="utr_authorization" name="utr_authorization">
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                        </div>
                        <div class="form-group d-none">
                            <label for="nino">NINO</label>
                            <input type="text" class="form-control my-2" id="nino" name="nino" placeholder="Enter NINO number">
                        </div>
                        <div class="text-center">
                            <button id="director-cancelButton" class="btn btn-sm btn-outline-dark" type="button">Cancel</button>
                            <button id="director-updateButton" class="btn btn-sm bg-theme text-light btn-outline-dark" style="display: none;">Update</button>
                            <button id="director-clearButton" class="btn btn-sm btn-outline-dark" type="button">Clear</button>
                            <button id="director-saveButton" class="btn btn-sm bg-theme text-light btn-outline-dark" type="submit">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div id="rightContainer" class="col-lg-9">
            <p class="p-2 mt-3 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center">
                Director Info List
            </p>
            <div class="border-theme p-3 border-1">
                <table id="directorTable" class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>NI Number</th>
                            <th>UTR Number</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($client->directorInfos))
                            @foreach($client->directorInfos as $directorInfo)
                                <tr data-director-id="{{ $directorInfo->id }}"
                                    data-client-id="{{ $directorInfo->client_id }}"
                                    data-director-info='@json($directorInfo)'>
                                    <td>{{ $directorInfo->name }} {{ $directorInfo->last_name }}</td>
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
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>