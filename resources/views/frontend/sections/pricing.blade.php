<section class="py-5 bg-primary">
    <div class="container">
        <div class="row py-5">
            <div class="col-lg-12">
                <div class="text-center w-100  mx-auto">
                    <div class=" px-5">
                        <h1 class="text-light poppins-bold">{{ $pricingHeading->short_title }} <span class="txt-secondary"> {{ $pricingHeading->long_title }}</h1>
                    </div>

                    <h3 class="text-light w-75 text-center my-3 mx-auto">
                        {!! $pricingHeading->long_description !!}
                    </h3>
                </div>

            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container">
        <div class="row">
            <ul class="nav nav-tabs d-inline-flex flex-wrap justify-content-center mx-auto w-auto rounded-3 px-0 overflow-hidden tab-customize" id="myTab" role="tablist">
                @foreach($packages as $index => $package)
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $index === 0 ? 'active' : '' }}" id="tab-{{ $index }}" data-bs-toggle="tab" data-bs-target="#tab-pane-{{ $index }}" type="button" role="tab" aria-controls="tab-pane-{{ $index }}" aria-selected="{{ $index === 0 ? 'true' : 'false' }}">{{ $package->short_title }}</button>
                </li>
                @endforeach
            </ul>
            <div class="tab-content col-lg-10 mx-auto" id="myTabContent">
                @foreach($packages as $index => $package)
                <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" id="tab-pane-{{ $index }}" role="tabpanel" aria-labelledby="tab-{{ $index }}" tabindex="0">
                    <div class="card p-4 mt-4">
                        <div class="row">
                            <div class="col-lg-12 text-center py-4">
                                <h5 class="txt-primary poppins-medium">{{ $package->short_title }}</h5>
                                <small class="txt-primary poppins-regular py-3">{!! $package->long_description !!}</small>
                                <h2 class="mt-3 mb-1 txt-primary poppins-bold" id="price-display-{{ $index }}">£{{ number_format($package->price, 0) }}</h2>
                                <p class="text-center poppins-regular txt-primary">+ VAT / Month</p>
                                <h6 class="txt-primary poppins-medium my-3">Price Depends on your requirements</h6>
                                <div class="mt-4">
                                    <a href="{{ route('frontend.getQuotation') }}#get-qoutation" class="poppins-medium btn-theme rounded-3 fs-5">Get Now</a>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-lg-12">
                                <h5 class="txt-primary poppins-medium my-4 ms-4 text-center text-md-start">Choose your Turn Over</h5>
                                <div class="d-flex flex-column flex-sm-row flex-wrap align-items-center gap-1 justify-content-center justify-content-md-around">
                                    @foreach($package->turnOvers as $key => $turnover)
                                    @php
                                        $featureIds = explode(',', trim($turnover->features, '"'));
                                        $features = \App\Models\PackageFeature::whereIn('id', $featureIds)->select('name', 'is_checked')->get()->toArray();
                                    @endphp
                                    <div class="mb-2">
                                        
                                        <input @if($key === 0) checked @endif type="radio" class="timepick invisible" name="timepick-{{ $index }}" id="turnover-{{ $turnover->id }}" data-price="{{ $turnover->price }}" data-index="{{ $index }}" data-features="{{ json_encode($features) }}">
                                        <label for="turnover-{{ $turnover->id }}">{{ $turnover->price_range }}</label>
                                    </div>
                                    @endforeach
                                    <div class="mb-2">
                                        <a href="{{ route('frontend.getQuotation') }}#get-qoutation" class="btn bg-primary text-light mx-auto rounded-3" style="border: none;">Get Customized Quote</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row my-3">
                            <div class="col-lg-12">
                                <h5 class="txt-primary poppins-medium my-4 ms-4 text-center text-md-start">You can customize your requirements from below</h5>
                                <ul class="list-theme txt-primary align-items-center" id="feature-list-{{ $index }}">
                                    @if($turnover->features)
                                    @foreach($features as $feature)
                                    <li class="border border-start-0 border-end-0 border-bottom-0 py-3">
                                        <div class="d-flex justify-content-between">
                                            {{ $feature['name'] }}
                                            <span class="float-end">
                                                @if($feature['is_checked'] === 1)
                                                <iconify-icon class="fs-3" icon="icon-park-outline:check-one"></iconify-icon>
                                                @endif
                                            </span>
                                        </div>
                                    </li>
                                    @endforeach
                                    @endif
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

<style>
    @media (max-width: 768px) {
        .tab-customize {
            flex-wrap: nowrap;
            overflow-x: auto;
        }
        .nav-item {
            flex: 1;
            display: flex;
            align-items: stretch;
        }
        .nav-link {
            width: 100%;
            text-align: center;
            padding: 10px 20px;
            border-radius: 15px 15px 0 0 !important;
            border: none;
        }
        .nav-link:hover,
        .nav-link:focus {
            color: #4154f1;
        }
        .nav-link.active {
            background-color: #fff;
            color: #4154f1;
            border-bottom: 2px solid #4154f1;
            border-radius: 15px 15px 0 0 !important;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const radios = document.querySelectorAll('input[type="radio"]');

        radios.forEach(radio => {
            radio.addEventListener('change', function() {
                const price = this.getAttribute('data-price');
                const index = this.getAttribute('data-index');
                const features = JSON.parse(this.getAttribute('data-features'));

                const priceDisplay = document.getElementById(`price-display-${index}`);
                const featureList = document.getElementById(`feature-list-${index}`);

                if (priceDisplay) {
                    priceDisplay.textContent = `£${parseFloat(price).toFixed(0)}`;
                }

                if (featureList) {
                    featureList.innerHTML = '';
                    features.forEach(feature => {
                        const li = document.createElement('li');
                        li.className = 'border border-start-0 border-end-0 border-bottom-0 py-3';
                        li.innerHTML = `
                            <div class="d-flex justify-content-between">
                                ${feature.name}
                                ${feature.is_checked ? '<span class="float-end"><iconify-icon class="fs-3" icon="icon-park-outline:check-one"></iconify-icon></span>' : ''}
                            </div>
                        `;
                        featureList.appendChild(li);
                    });
                }
            });
        });
    });
</script>