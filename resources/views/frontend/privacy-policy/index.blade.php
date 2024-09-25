@extends('frontend.layouts.frontend')

@section('meta_title', $meta->meta_title)
@section('meta_description', $meta->meta_description)
@section('meta_image', asset('images/meta_image/'.$meta->meta_image))

@section('content')
<section class="py-5 bg-primary">
    <div class="container">
        <div class="row py-5">
            <div class="col-lg-12">
                <div class="text-center w-100  mx-auto">
                    <div class=" px-5">
                        <h1 class="text-light poppins-bold">{{ $privacyPolicy->short_title }}</h1>
                    </div>
                    <p class="text-light w-75 text-center my-3 mx-auto">
                        {{ $privacyPolicy->long_title }}
                    </p>
                </div>

            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container">
        <div class="row py-2 col-lg-12 mx-auto py-3">
            <h1 class="txt-primary text-center text-capitalize poppins-bold mb-5">{{ $privacyPolicy->short_description }}</h1>
            {!! $privacyPolicy->long_description !!}
            <div class="col-lg-12 text-center mt-5"> 
                <h2 class="txt-primary text-center text-capitalize poppins-bold mb-4">Thinking about setting up new Business?</h2>
                <a href="{{ route('frontend.getQuotation') }}" class="btn bg-primary py-2 px-5 poppins-bold text-white"style="border: none;">Book your Appointment</a>
                 
            </div>
        </div>
    </div>
</section>
@endsection