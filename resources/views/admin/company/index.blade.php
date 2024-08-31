@extends('admin.layouts.admin')

@section('content')

<section class="content pt-3" id="addThisFormContainer">

  <div class="container-fluid">
    <div class="row justify-content-md-center">
      <!-- right column -->
      <div class="col-md-12">
        <!-- general form elements disabled -->

    @if(session()->has('success'))
        <div class="alert alert-success pt-3 mb-3" id="successMessage">{{ session()->get('success') }}</div>
    @endif

    @if(session()->has('error'))
        <div class="alert alert-danger pt-3 mb-3" id="errMessage">{{ session()->get('error') }}</div>
    @endif

        <div class="card card-secondary">
          <div class="card-header">
            <h3 class="card-title">Comapny Informations</h3>
          </div>
          
 

          <!-- /.card-header -->
          <div class="card-body">
            <div class="ermsg"></div>
            <form id="createThisForm" action="{{ route('admin.companyDetails') }}" method="POST" enctype="multipart/form-data">
              @csrf
              <input type="hidden" class="form-control" id="codeid" name="codeid" value="{{$data->id}}">
              <div class="row">

                <div class="col-sm-4">
                  <div class="form-group">
                    <label>Company name*</label>
                    <input type="text" class="form-control @error('company_name') is-invalid @enderror" id="company_name" name="company_name" value="{{$data->company_name}}">
                    @error('company_name')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                  </div>
                </div>

                <div class="col-sm-4">
                    <div class="form-group">
                    <label>Email (1)</label>
                    <input type="email" class="form-control @error('email1') is-invalid @enderror" id="email1" name="email1" value="{{$data->email1}}">
                    </div>
                </div>

                <div class="col-sm-4">
                    <div class="form-group">
                    <label>Email (2)</label>
                    <input type="email" class="form-control @error('email2') is-invalid @enderror" id="email2" name="email2" value="{{$data->email2}}">
                    </div>
                </div>

                <div class="col-sm-3">
                  <div class="form-group">
                    <label>Phone (1)</label>
                    <input type="text" class="form-control @error('phone1') is-invalid @enderror" id="phone1" name="phone1"  value="{{$data->phone1}}">
                  </div>
                </div>

                <div class="col-sm-3">
                    <div class="form-group">
                      <label>Phone (2)</label>
                      <input type="text" class="form-control @error('phone2') is-invalid @enderror" id="phone2" name="phone2" value="{{$data->phone2}}">
                    </div>
                </div>

                <div class="col-sm-3">
                    <div class="form-group">
                      <label>Phone (3)</label>
                      <input type="text" class="form-control @error('phone3') is-invalid @enderror" id="phone3" name="phone3" value="{{$data->phone3}}">
                    </div>
                </div>

                <div class="col-sm-3">
                    <div class="form-group">
                    <label>Phone (4)</label>
                    <input type="text" class="form-control @error('phone4') is-invalid @enderror" id="phone4" name="phone4" value="{{$data->phone4}}">
                    </div>
                </div>

                <div class="col-sm-6">
                    <div class="form-group">
                    <label>Address (1)</label>
                    <input type="text" class="form-control @error('address1') is-invalid @enderror" id="address1" name="address1" value="{{$data->address1}}">
                    </div>
                </div>

                <div class="col-sm-6">
                    <div class="form-group">
                    <label>Address (2)</label>
                    <input type="text" class="form-control @error('address2') is-invalid @enderror" id="address2" name="address2" value="{{$data->address2}}">
                    </div>
                </div>

                <div class="col-sm-4">
                    <div class="form-group">
                    <label>Website</label>
                    <input type="text" class="form-control @error('website') is-invalid @enderror" id="facebook" name="facebook" value="{{$data->facebook}}">
                    </div>
                </div>

                <div class="col-sm-4">
                    <div class="form-group">
                    <label>Facebook</label>
                    <input type="text" class="form-control @error('facebook') is-invalid @enderror" id="facebook" name="facebook" value="{{$data->facebook}}">
                    </div>
                </div>

                <div class="col-sm-4">
                    <div class="form-group">
                    <label>Instagram</label>
                    <input type="text" class="form-control @error('instagram') is-invalid @enderror" id="instagram" name="instagram" value="{{$data->instagram}}">
                    </div>
                </div>

                <div class="col-sm-4">
                    <div class="form-group">
                    <label>Twitter</label>
                    <input type="text" class="form-control @error('twitter') is-invalid @enderror" id="twitter" name="twitter" value="{{$data->twitter}}">
                    </div>
                </div>

                <div class="col-sm-4">
                    <div class="form-group">
                    <label>LinkedIn</label>
                    <input type="text" class="form-control @error('linkedin') is-invalid @enderror" id="linkedin" name="linkedin" value="{{$data->linkedin}}">
                    </div>
                </div>

                <div class="col-sm-4">
                    <div class="form-group">
                    <label>Youtube</label>
                    <input type="text" class="form-control @error('youtube') is-invalid @enderror" id="youtube" name="youtube" value="{{$data->youtube}}">
                    </div>
                </div>

                <div class="col-sm-4">
                    <div class="form-group">
                    <label>Tawkto</label>
                    <input type="text" class="form-control @error('tawkto') is-invalid @enderror" id="tawkto" name="tawkto" value="{{$data->tawkto}}">
                    </div>
                </div>

                <div class="col-sm-4">
                    <div class="form-group">
                    <label>App store link</label>
                    <input type="text" class="form-control @error('google_appstore_link') is-invalid @enderror" id="google_appstore_link" name="google_appstore_link" value="{{$data->google_appstore_link}}">
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                    <label>google playstore link</label>
                    <input type="text" class="form-control @error('google_play_link') is-invalid @enderror" id="google_play_link" name="google_play_link" value="{{$data->google_play_link}}">
                    </div>
                </div>

                <div class="col-sm-4">
                    <div class="form-group">
                    <label>Opening Time</label>
                    <input type="time" class="form-control @error('opening_time') is-invalid @enderror" id="opening_time" name="opening_time" value="{{$data->opening_time}}">
                    </div>
                </div>

                <div class="col-sm-4">
                    <div class="form-group">
                    <label>Footer Link</label>
                    <input type="text" class="form-control @error('footer_link') is-invalid @enderror" id="footer_link" name="footer_link" value="{{$data->footer_link}}">
                    </div>
                </div>

                <div class="col-sm-4 d-none">
                    <div class="form-group">
                    <label>Currency</label>
                        <select class="form-control" id="currency" name="currency">
                            <option value="" disabled selected>Please choose currency</option>
                            <option value="$" @if (!empty($data->currency) && $data->currency == '$') selected @endif>$</option>
                            <option value="£" @if (!empty($data->currency) && $data->currency == '£') selected @endif>£</option>
                            <option value="€" @if (!empty($data->currency) && $data->currency == '€') selected @endif>€</option>
                            <option value="৳" @if (!empty($data->currency) && $data->currency == '৳') selected @endif>৳</option>
                        </select>
                    </div>
                </div>

                <div class="col-sm-12">
                    <div class="form-group">
                    <label>About Us</label>
                        <textarea name="about_us" id="about_us" class="form-control @error('about_us') is-invalid @enderror summernote" cols="30" rows="3">{{$data->about_us}}</textarea>
                    </div>
                </div>

                <div class="col-sm-12">
                    <div class="form-group">
                    <label>Footer Content</label>
                    <textarea name="footer_content" id="footer_content" class="form-control @error('footer_content') is-invalid @enderror" cols="30" rows="3">{{$data->footer_content}}</textarea>
                    </div>
                </div>

                <div class="col-sm-12">
                    <div class="form-group">
                    <label>Google Map source code</label>
                    <textarea name="google_map" id="google_map" class="form-control @error('google_map') is-invalid @enderror" cols="30" rows="3">{{$data->google_map}}</textarea>
                    </div>
                </div>
                
                <div class="col-sm-4">
                    <div class="form-group">
                        <label>Fav Icon*</label>
                        <input type="file" class="form-control @error('fav_icon') is-invalid @enderror" id="fav_icon" name="fav_icon" onchange="previewImage(event, 'fav_icon_preview')">
                        
                        @error('fav_icon')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="card card-outline card-info">
                        <div class="card-body pt-3">
                            <img id="fav_icon_preview" src="{{ isset($data->fav_icon) ? asset('images/company/'.$data->fav_icon) : '' }}" alt="" style="width: 230px">
                        </div>
                    </div>
                </div>

                <div class="col-sm-4">
                    <div class="form-group">
                        <label>Company Logo*</label>
                        <input type="file" class="form-control @error('company_logo') is-invalid @enderror" id="company_logo" name="company_logo" onchange="previewImage(event, 'company_logo_preview')">
                        
                        @error('company_logo')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="card card-outline card-info">
                        <div class="card-body pt-3">
                            <img id="company_logo_preview" src="{{ isset($data->company_logo) ? asset('images/company/'.$data->company_logo) : '' }}" alt="" style="width: 230px">
                        </div>
                    </div>
                </div>

                <div class="col-sm-4">
                    <div class="form-group">
                        <label>Footer logo</label>
                        <input type="file" class="form-control @error('footer_logo') is-invalid @enderror" id="footer_logo" name="footer_logo" onchange="previewImage(event, 'footer_logo_preview')">
                        
                        @error('footer_logo')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="card card-outline card-info">
                        <div class="card-body pt-3">
                            <img id="footer_logo_preview" src="{{ isset($data->footer_logo) ? asset('images/company/'.$data->footer_logo) : '' }}" alt="" style="width: 230px">
                        </div>
                    </div>
                </div>

                
              </div>
            
          </div>

          
          <!-- /.card-body -->
          <div class="card-footer">
            <button type="submit" class="btn btn-secondary" value="Update">Update</button>
          </div>
        </form>
          <!-- /.card-footer -->
          <!-- /.card-body -->
        </div>
      </div>
      <!--/.col (right) -->
    </div>
    <!-- /.row -->
  </div>
</section>

@endsection
@section('script')

<script>
    $(document).ready(function() {
        $('.summernote').summernote({
            height: 200, 
        });
    });

    function previewImage(event, previewId) {
        const reader = new FileReader();
        reader.onload = function(){
            const output = document.getElementById(previewId);
            output.src = reader.result;
        };
        reader.readAsDataURL(event.target.files[0]);
    }
</script>

@endsection