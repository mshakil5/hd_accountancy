@extends('admin.layouts.admin')

@section('content')

<section class="content" id="addThisFormContainer">
  <div class="container-fluid">
    <div class="row justify-content-md-center">
      <div class="col-md-10">
        <div class="card card-secondary">
          <div class="card-header">
            <h3 class="card-title">Terms and Conditions Page Meta Data</h3>
          </div>
          <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

              <div class="ermsg"></div>
              <form id="updateThisForm" action="{{ route('termsMeta.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-sm-12">
                        <div class="form-group">
                            <label for="meta_title">Meta Title</label>
                            <input type="text" class="form-control @error('meta_title') is-invalid @enderror" id="meta_title" name="meta_title" value="{{ old('meta_title', $data->meta_title ?? '') }}" placeholder="Enter meta title">
                            @error('meta_title')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-sm-12">
                        <div class="form-group">
                            <label for="meta_description">Meta Description</label>
                            <textarea class="form-control summernote @error('meta_description') is-invalid @enderror" id="meta_description" name="meta_description">
                                @if(isset($data->meta_description))
                                    {{ $data->meta_description }}
                                @endif
                            </textarea>
                            @error('meta_description')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-sm-12">
                        <div class="form-group">
                            <label for="meta_image">Meta Image</label>
                            <input type="file" id="meta_image" name="meta_image" class="form-control" onchange="previewMetaImage(event)" accept="image/*">
                            <img id="meta_image_preview" src="{{ old('meta_image', optional($data)->meta_image ? asset('images/meta_image/' . $data->meta_image) : '') }}" alt="Meta Image Preview" class="pt-3" style="max-width: 250px; height: auto;"/>
                            @error('meta_image')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
              </form>
          </div>
          <div class="card-footer">

              <button type="submit" id="updateBtn" class="btn btn-secondary" value="Update" form="updateThisForm">Update</button>

              <div class="loader text-center" style="display: none;">
                  <div class="spinner-border text-primary" role="status">
                      <span class="visually-hidden">Loading...</span>
                  </div>    
              </div>

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
            callbacks: {
                onChange: function(contents) {
                    $('#meta_description').val(contents);
                }
            }
        });
    });
</script>

<script>
    document.querySelector('#updateThisForm').addEventListener('submit', function() {
        document.querySelector('.loader').style.display = 'block';
        document.querySelector('#updateBtn').disabled = true;
    });
</script>
@endsection