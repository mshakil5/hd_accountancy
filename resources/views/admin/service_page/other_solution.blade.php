@extends('admin.layouts.admin')

@section('content')

<section class="content" id="addThisFormContainer">
  <div class="container-fluid">
    <div class="row justify-content-md-center">
      <div class="col-md-10">
        <div class="card card-secondary">
          <div class="card-header">
            <h3 class="card-title">Home Page Values</h3>
          </div>
          <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if($otherSolution)
              <div class="ermsg"></div>
              <form id="updateThisForm" action="{{ route('servicepageOtherSolution.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row">
                  <div class="col-sm-12">
                    <div class="form-group">
                      <label for="short_title">Short Title</label>
                      <input type="text" class="form-control @error('short_title') is-invalid @enderror" id="short_title" name="short_title" value="{{ old('short_title', $otherSolution->short_title) }}" placeholder="Enter short title">
                      @error('short_title')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                      @enderror
                    </div>
                  </div>
                  <div class="col-sm-12">
                    <div class="form-group">
                      <label for="long_title">Long Title</label>
                      <input type="text" class="form-control @error('long_title') is-invalid @enderror" id="long_title" name="long_title" value="{{ old('long_title', $otherSolution->long_title) }}" placeholder="Enter long title">
                      @error('long_title')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                      @enderror
                    </div>
                  </div>
                  <div class="col-sm-12">
                    <div class="form-group">
                      <label for="short_description">Short Description</label>
                      <input type="text" class="form-control @error('short_description') is-invalid @enderror" id="short_description" name="short_description" value="{{ old('short_description', $otherSolution->short_description) }}" placeholder="Enter long title">
                      @error('short_description')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                      @enderror
                    </div>
                  </div>
                  <div class="col-sm-12">
                    <div class="form-group">
                      <label for="long_description">Long Description</label>
                      <textarea class="form-control summernote @error('long_description') is-invalid @enderror" id="long_description" name="long_description">{{ old('long_description', $otherSolution->long_description) }}</textarea>
                      @error('long_description')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                      @enderror
                    </div>
                  </div>
                </div>
              </form>
            @else
              <p class="alert alert-warning">No information found.</p>
            @endif
          </div>
          <div class="card-footer">
            @if($otherSolution)
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
