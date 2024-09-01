@extends('admin.layouts.admin')

@section('content')

<section class="content" id="addThisFormContainer">
  <div class="container-fluid">
    <div class="row justify-content-md-center">
      <div class="col-md-8">
        <div class="card card-secondary">
          <div class="card-header">
            <h3 class="card-title">Edit Contact Page</h3>
          </div>
          <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if($contactHeading)
              <div class="ermsg"></div>
              <form id="updateThisForm" action="{{ route('admin.contact.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row">
                  <div class="col-sm-6">
                    <div class="form-group">
                      <label for="name">Name</label>
                      <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $contactHeading->name) }}" placeholder="Enter name">
                      @error('name')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                      @enderror
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="form-group">
                      <label for="short_title">Short Title</label>
                      <input type="text" class="form-control @error('short_title') is-invalid @enderror" id="short_title" name="short_title" value="{{ old('short_title', $contactHeading->short_title) }}" placeholder="Enter short title">
                      @error('short_title')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                      @enderror
                    </div>
                  </div>
                  <div class="col-sm-12">
                    <div class="form-group">
                      <label for="long_title">Long Title</label>
                      <input type="text" class="form-control @error('long_title') is-invalid @enderror" id="long_title" name="long_title" value="{{ old('long_title', $contactHeading->long_title) }}" placeholder="Enter long title">
                      @error('long_title')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                      @enderror
                    </div>
                  </div>
                  <div class="col-sm-12">
                    <div class="form-group">
                      <label for="short_description">Short Description</label>
                      <input type="text" class="form-control @error('short_description') is-invalid @enderror" id="short_description" name="short_description" value="{{ old('short_description', $contactHeading->short_description) }}" placeholder="Enter short description">
                      @error('short_description')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                      @enderror
                    </div>
                  </div>
                  <div class="col-sm-12">
                    <div class="form-group">
                      <label for="long_description">Long Description</label>
                      <textarea class="form-control summernote @error('long_description') is-invalid @enderror" id="long_description" name="long_description">{{ old('long_description', $contactHeading->long_description) }}</textarea>
                      @error('long_description')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                      @enderror
                    </div>
                  </div>
                  <div class="col-sm-12">
                    <div class="form-group">
                      <label for="meta_title">Meta Title</label>
                      <input type="text" class="form-control @error('meta_title') is-invalid @enderror" id="meta_title" name="meta_title" value="{{ old('meta_title', $contactHeading->meta_title) }}" placeholder="Enter meta title">
                      @error('meta_title')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                      @enderror
                    </div>
                  </div>
                  <div class="col-sm-12">
                    <div class="form-group">
                      <label for="meta_description">Meta Description</label>
                      <textarea class="form-control @error('meta_description') is-invalid @enderror" id="meta_description" name="meta_description">{{ old('meta_description', $contactHeading->meta_description) }}</textarea>
                      @error('meta_description')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                      @enderror
                    </div>
                  </div>
                  <div class="col-sm-12">
                    <div class="form-group">
                      <label for="meta_image">Meta Image</label>
                      <input type="file" id="meta_image" name="meta_image" class="form-control" onchange="previewMetaImage(event)" accept="image/*">
                      <img id="meta_image_preview" src="{{ old('meta_image', asset('images/meta_image/' . $contactHeading->meta_image)) }}" alt="Meta Image Preview" class="pt-3" style="max-width: 250px; height: auto;"/>
                    </div>
                  </div>
                </div>
              </form>
            @else
              <p class="alert alert-warning">No contact information found to edit. Please check the database or create a new entry.</p>
            @endif
          </div>
          <div class="card-footer">
            @if($contactHeading)
              <button type="submit" id="updateBtn" class="btn btn-secondary" value="Update" form="updateThisForm">Update</button>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

@endsection

@section('script')

<script>
    function previewMetaImage(event) {
        var output = document.getElementById('meta_image_preview');
        output.src = URL.createObjectURL(event.target.files[0]);
        output.style.display = 'block';
    }

    $(document).ready(function() {
        $('.summernote').summernote({
            height: 200, 
        });
    });
</script>

@endsection
