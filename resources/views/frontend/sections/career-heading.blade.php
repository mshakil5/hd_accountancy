<section class="py-5 bg-primary">
  <div class="container">
    <div class="row py-5">
      <div class="col-lg-5">
        <img src="{{ asset('images/meta_image/' . $career->meta_image) }}" class="img-fluid" alt="">
      </div>
      <div class="col-lg-7 d-flex align-items-center">
        <div class="p-4">
          <h1 class="display-5 text-white poppins-bold">{{ $career->short_title }}</h1>
          <p class="h2 text-white poppins-medium my-3">
            {{ $career->long_title }}
          </p>
          <p class="text-white poppins-regular w-75">
            {!! $career->long_description !!}
          </p>
        </div>
      </div>
    </div>
  </div>
</section>