<section class="py-5 bg-primary">
    <div class="container">
        <div class="row py-5">
            <div class="col-lg-12">
                <div class="text-center w-100  mx-auto">
                    <div class=" px-5">
                        <h1 class="text-light poppins-bold">{{ $faq->short_title }}</h1>
                    </div>
                    <p class="text-light w-75 text-center my-3 mx-auto">
                        {{ $faq->long_title }}
                    </p>
                </div>

            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container">
        <div class="row py-2 col-lg-12 mx-auto py-3">
            <h1 class="txt-primary text-center text-capitalize poppins-bold mb-5">{{ $faq->short_description }}</h1>
            {!! $faq->long_description !!}
            <div class="col-lg-12 text-center mt-5"> 
                <h2 class="txt-primary text-center text-capitalize poppins-bold mb-4">Thinking about setting up a new Business?</h2>
                <a href="{{ route('frontend.getQuotation') }}" class="btn bg-primary py-2 px-5 poppins-bold text-white"style="border: none;">Book your Appointment</a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 pt-5">
                <div class="">
                    <h2 class="poppins-bold text-center txt-primary">Frequently Asked Questions</h2>
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
        </div>
    </div>
</section>


<style>
    .faq-container {
        width: 80%;
        border-radius: 8px;
        padding: 20px;
        margin: auto;
    }

    .faq-item {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        margin-bottom: 10px;
        padding: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
    }

    .faq-item:hover {
        background-color: #f1f1f1;
    }

    .faq-question {
        font-size: 16px;
        color: #1a2e6c;
    }

    .faq-icon {
        color: #1a2e6c;
        transition: transform 0.2s ease;
    }

    .faq-answer {
        padding-top: 10px;
        font-size: 14px;
        color: #333;
        display: none;
    }

</style>