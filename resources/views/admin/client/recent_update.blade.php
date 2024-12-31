<div class="container-fluid">
    <div class="row">
        <!-- Left Container -->
        <div id="leftContainer" class="col-lg-3">
            <div class="row">
                <div class="col-12 text-right my-3">
                    <button id="createNewButton2" class="btn bg-theme text-light btn-outline-dark">Create New</button>
                </div>
            </div>

            <div class="row" id="recentUpdateFormContainer" style="display: none;">
                <div class="col-12">
                    <form id="recentUpdateForm">
                        <input type="hidden" id="recentUpdateIdInput" name="id">
                        <input type="hidden" id="clientIdInput" name="client_id" value="{{ $client->id }}">
                        <div class="form-group">
                            <label for="note">Note <span class="text-danger">*</span></label>
                            <textarea class="form-control my-2" id="note" name="note" placeholder="Enter note" rows="5" required></textarea>
                        </div>
                        <div class="text-center">
                            <button id="recentUpdate-cancelButton" class="btn btn-sm btn-outline-dark" type="button">Cancel</button>
                            <button id="recentUpdate-updateButton" class="btn btn-sm bg-theme text-light btn-outline-dark" style="display: none;">Update</button>
                            <button id="recentUpdate-clearButton" class="btn btn-sm btn-outline-dark">Clear</button>
                            <button id="recentUpdate-saveButton" class="btn btn-sm bg-theme text-light btn-outline-dark">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Container -->
        <div id="rightContainer" class="col-lg-9">
            <p class="p-2 mt-3 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center">
                Recent Updates List
            </p>
            <div class="border-theme p-3 border-1">
                <table id="recentUpdateTable" class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Note</th>
                            <th>Created By</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($client->recentUpdates as $update)
                            <tr data-recent-update='@json($update)'>
                                <td>{{ date('d-m-Y', strtotime($update->created_at)) }}</td>
                                <td>{!! Str::limit($update->note, 50) !!}</td>
                                <td>{{ $update->user->first_name ?? 'N/A' }}</td>
                                <td>
                                    <a type="button" class="fa fa-edit edit-recent-update" style="font-size: 20px;"></a>
                                    <a type="button" class="fas fa-trash delete-recent-update" style="color: red; font-size: 20px;"></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>