@extends('frontend.layouts.frontend')

@section('content')

<section class="mb-5">
    <div class="container mb-5">
        <div class="row justify-content-center">

                <div class="content mt-4">
                    <h3 class="txt-primary poppins-medium display-6">{{ $businessService->short_title }}</h3>
                    <hr class="my-4">
                    <p class="txt-primary lead" style="text-align: justify;">
                        {!! $businessService->long_description !!}
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection