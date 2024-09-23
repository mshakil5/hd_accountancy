@extends('frontend.layouts.frontend')

@section('content')

<section class="py-5 bg-primary">
    <div class="container">
        <div class="row py-5">
            <div class="col-lg-12">
                <div class="text-center w-100  mx-auto">
                    <div class=" px-5">
                        <h1 class="text-light poppins-bold">{{ $businessService->short_description }}</h1>
                    </div>
                    <p class="text-light w-75 text-center my-3 mx-auto">
                        {!! $businessService->long_title !!}
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

{!! $businessService->long_description !!}

@if($businessService->short_title === 'I want to set Up New Business')

<section class="pb-5">
    <div class="container ">
        <div class="row">
            <div class="col-lg-12">
                <div class="text-center">
                    <h2 class="txt-primary poppins-bold text-capitalize">Frequently Asked Questions</h2>
                </div>
            </div>
        </div>

        <div class="row py-4">
            <div class="text-center">
                <div class="accordion accordion-flush w-75 mx-auto" id="accordionFlushExample">
                    @foreach($faqQuestions as $faqQuestion)
                        <div class="accordion-item my-3 shadow border border-1 rounded-4 overflow-hidden">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed txt-primary poppins-bold fs-5" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapse-{{ $faqQuestion->id }}" aria-expanded="false" aria-controls="flush-collapse-{{ $faqQuestion->id }}">
                                    {{ $faqQuestion->question }}
                                </button>
                            </h2>
                            <div id="flush-collapse-{{ $faqQuestion->id }}" class="accordion-collapse collapse" data-bs-parent="#accordionFlushExample">
                                <div class="accordion-body text-start">
                                    {!! $faqQuestion->answer !!}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

@endif

@endsection