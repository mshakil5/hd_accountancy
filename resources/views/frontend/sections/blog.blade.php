<section class="py-5 position-relative">
    <div class="container">
        <div class="row text-center">
            <div class="title-with-sub-title" style="background-color: #f7f8fe;">
                <h2 class="poppins-bold txt-primary">Latest Insights</h2>
                <br>
                <h5 class="poppins-medium txt-primary txt-primary">Level Up Your Knowledge</h5>
            </div>
        </div>
        <div class="row mt-5">
            @foreach($latestInsights as $item)
                <div class="col-lg-4">
                    <a href="{{ route('latest-insights.show', $item->slug) }}">
                        <img src="{{ asset($item->image) }}" class="img-fluid">
                        <h5 class="poppins-medium txt-primary my-3">
                            {{ $item->short_title }}
                        </h5>
                    </a>
                    <p class="txt-primary">
                        {{ $item->short_description }}
                    </p>
                </div>
            @endforeach
            <div class="col-12 text-center my-5">
                <a href="{{ route('frontend.latestInsights') }}" class="btn btn-theme-outline d-inline w-50 mx-auto rounded-3 fs-6">See More Article</a>
            </div>
        </div>
    </div>
</section>