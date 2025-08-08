@extends('frontend.layouts.frontend')

@section('meta_title', $service->meta_title ?? 'HD Accountancy')
@section('meta_description', $service->meta_description ?? 'HD Accountancy')
@section('meta_keywords', $service->meta_keywords ?? 'HD Accountancy, Accountancy, Accountant, Book keeping')
@section('meta_image', $service->meta_image ? asset('/' . $service->meta_image) : '')

@section('content')

<section class="py-5 bg-primary">
    <div class="container">
        <div class="row py-5">
            <div class="col-lg-12">
                <div class="text-center w-100  mx-auto">
                    <div class=" px-5">
                        <h1 class="text-light poppins-bold">{{ $service->short_title }}</h1>
                    </div>
                    {!! $service->header_description !!}
                </div>

            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container">
        <div class="row py-2 col-lg-12 mx-auto py-3">
            {!! $service->long_description !!}
            <div class="col-lg-6 text-center">
            <img src="{{ asset('/' . $service->details_image) }}" class="img-fluid" alt="">
            </div>
            <div class="col-lg-12 text-center mt-5"> 
                <h2 class="txt-primary text-center text-capitalize mb-3" style="font-weight: 600; font-size: 36px;">See what our clients says </h2> 
            </div>
            <div class="row ">
            {!! $service->short_description !!}
            </div>
            <div class="col-lg-12 text-center mt-5"> 
                <h2 class="txt-primary text-center text-capitalize poppins-bold mb-4">{{$service->footer_title}}</h2>
                <a href="{{ route('frontend.contact') }}" class="btn bg-primary py-2 px-5 poppins-bold text-white"style="border: none;">Book your Appointment</a>
                 
            </div>
        </div>
    </div>
</section>

@endsection