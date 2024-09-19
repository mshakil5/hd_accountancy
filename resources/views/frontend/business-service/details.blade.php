@extends('frontend.layouts.frontend')

@section('content')

<section class="py-5 bg-primary">
    <div class="container">
        <div class="row py-5">
            <div class="col-lg-12">
                <div class="text-center w-100  mx-auto">
                    <div class=" px-5">
                        <h1 class="text-light poppins-bold">{{ $businessService->long_title }}</h1>
                    </div>
                    <p class="text-light w-75 text-center my-3 mx-auto">
                        {!! $businessService->short_description !!}
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

{!! $businessService->long_description !!}

@endsection