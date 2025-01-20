<form id="aboutBusinessForm">
    <div class="row my-4">
        <div class="col-lg-12">
            <textarea class="form-control summernote" id="about_business" name="about_business" placeholder="Enter details about the business">
            {!! $client->about_business ?? '' !!}
            </textarea>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-4 mx-auto text-center">
            <button id="aboutBusiness-updateButton" class="btn btn-sm bg-theme text-light btn-outline-dark">Update</button>
        </div>
    </div>
</form>