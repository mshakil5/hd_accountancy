<section class="mb-5">
    <div class="container mb-5">
        <div class="row justify-content-center py-5">
            <div class="col-md-8 text-center">
                <h2 class="txt-primary poppins-bold display-4">{{ $latestInsight->short_title }}</h2>
                <p class="text-muted mt-3">
                    {{ $latestInsight->long_title }}
                </p>
                <p class="text-muted small mt-2">
                    Published {{ $latestInsight->created_at->diffForHumans() }}
                </p>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="text-center mb-4">
                    <img src="{{ asset($latestInsight->image) }}" class="img-fluid rounded shadow-lg" alt="{{ $latestInsight->short_title }}" style="max-height: 500px; width: 800px; object-fit: cover;">
                </div>

                <div class="content mt-4">
                    <h3 class="txt-primary poppins-medium display-6">{{ $latestInsight->short_description }}</h3>
                    <hr class="my-4">
                    <p class="txt-primary lead">
                        {!! $latestInsight->long_description !!}
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>


