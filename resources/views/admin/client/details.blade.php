<form id="detailsForm">
    <div class="row my-4">
        <div class="col-lg-9">
        </div>
        <div class="col-lg-3 text-center">
            <div class="img mb-2">
                <img src="{{ $client->photo ? asset('images/client/' . $client->photo) : asset('assets/img/human-placeholder.jpg') }}" id="imagePreview" width="150" class="border-theme border-2 rounded-3">
            </div>
            <label for="pic" class="mb-0" style="cursor: pointer;">
                <i class="bi bi-cloud-upload"></i>
                <small>Update Image</small>
            </label>
            <input type="file" id="pic" name="photo" class="invisible">
        </div>
        <div class="col-lg-4">
            <label for="">Email <span class="text-danger">*</span></label>
            <input type="email" class="form-control my-2" id="email" name="email" placeholder="Enter email" value="{{ $client->email }}">
        </div>
        <div class="col-lg-4">
            <label for="">Primary Phone <span class="text-danger">*</span></label>
            <input type="number" class="form-control my-2" id="phone" name="phone" placeholder="Enter phone" value="{{ $client->phone }}">
        </div>
        <div class="col-lg-4">
            <label for="">Secondary Phone</label>
            <input type="number" class="form-control my-2" id="phone2" name="phone2" placeholder="Enter phone" value="{{ $client->phone2 }}">
        </div>
        <div class="col-lg-4">
            <label for="">Trading Address</label>
            <textarea class="form-control my-2" id="trading_address" name="trading_address" placeholder="Enter trading address">{{ $client->trading_address }}</textarea>
        </div>
        <div class="col-lg-4">
            <label for=""> Registered Address Line 1</label>
            <textarea class="form-control my-2" id="address_line1" name="address_line1" placeholder="Enter address line 1">{{ $client->address_line1 }}</textarea>
        </div>
        <div class="col-lg-4">
            <label for="">Address Line 2</label>
            <textarea class="form-control my-2" id="address_line2" name="address_line2" placeholder="Enter address line 2">{{ $client->address_line2 }}</textarea>
        </div>
        <div class="col-lg-4 d-none">
            <label for="">Address Line 3</label>
            <textarea class="form-control my-2" id="address_line3" name="address_line3" placeholder="Enter address line 3">{{ $client->address_line3 }}</textarea>
        </div>
        <div class="col-lg-4">
            <label for="">City</label>
            <input type="text" class="form-control my-2" id="city" name="city" placeholder="Enter city" value="{{ $client->city }}">
        </div>
        <div class="col-lg-4 d-none">
            <label for="">Town</label>
            <input type="text" class="form-control my-2" id="town" name="town" placeholder="Enter state" value="{{ $client->town }}">
        </div>
        <div class="col-lg-4">
            <label for="">Postal Code</label>
            <input type="text" class="form-control my-2" id="postcode" name="postcode" placeholder="Enter postal code" value="{{ $client->postcode }}">
        </div>
        <div class="col-lg-4 d-none">
            <label for="country">Country</label>
            <div class="mt-2">
                <select class="form-control my-2" id="country" name="country">
                    <option value="">Choose Country</option>
                    <option value="UK" {{ $client->country == 'UK' ? 'selected' : '' }}>UK</option>
                </select>
            </div>
        </div>
        <div class="col-lg-4">
            <label for="" class="mb-2">Upload Photo Id </label>
            <div class="position-relative">
                <input type="file" class="form-control" name="photo_id" id="photo_id"
                    accept="image/*" placeholder="Upload photo id">
                <i class="bi bi-paperclip position-absolute top-50 translate-middle-y"
                    style="right: 8px;"></i>
            </div>
        </div>

    </div>
    <div class="row">
        <div class="col-lg-4 mx-auto text-center">
            <button type="submit" id="details-saveButton" class="btn btn-sm bg-theme text-light btn-outline-dark">Update</button>
        </div>
    </div>
</form>