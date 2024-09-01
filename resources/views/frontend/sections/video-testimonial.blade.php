<section class="py-5 bg-primary">
    <div class="container">
        <div class="row py-5">
            <div class="col-lg-12">
                <div class="text-center w-100  mx-auto">
                    <div class=" px-5">
                        <h1 class="text-light poppins-bold">Clients Testimonials</h1>
                        <h5 class="text-light poppins-bold">Your Voice is Our Inspiration</h5>
                    </div>
                    <p class="text-light w-75 text-center my-3 mx-auto">
                        We understand the importance of transparency and trust. That's why we're proud to share real client experiences through these video testimonials. Listen directly to how HD Accountancy Services has made a positive impact on their businesses
                    </p>
                </div>

            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container">
        <div class="row py-2 col-lg-12 mx-auto py-3 d-flex flex-wrap justify-content-center">
            <div class="col-lg-4 mb-4">
                <video controls  poster="{{ asset('assets/frontend/images/v1.png') }}" width="100% " class="rounded-2">
                    <source src="{{ asset('assets/frontend/videos/3145-166335957.mp4') }}" type="video/mp4">
                </video>
                <h6 class="txt-primary poppins-bold my-2">Alex Anderson, ADS Kitchen</h6>
            </div>
            <div class="col-lg-4 mb-4">
                <video controls  poster="{{ asset('assets/frontend/images/v2.png') }}" width="100% " class="rounded-2">
                    <source src="{{ asset('assets/frontend/videos/3145-166335957.mp4') }}" type="video/mp4">
                </video>
                <h6 class="txt-primary poppins-bold my-2">Babar Hussain, BH Electrical LTD</h6>
            </div>
            <div class="col-lg-4 mb-4">
                <video controls  poster="{{ asset('assets/frontend/images/v3.png') }}" width="100% " class="rounded-2">
                    <source src="{{ asset('assets/frontend/videos/3145-166335957.mp4') }}" type="video/mp4">
                </video>
                <h6 class="txt-primary poppins-bold my-2">Sam & Paige Goldthorpe, Distribution Jointing Services</h6>
            </div>
            <div class="col-lg-4 mb-4">
                <video controls  poster="{{ asset('assets/frontend/images/v4.png') }}" width="100% " class="rounded-2">
                    <source src="{{ asset('assets/frontend/videos/3145-166335957.mp4') }}" type="video/mp4">
                </video>
                <h6 class="txt-primary poppins-bold my-2">Alex Myers, Yorkshire DPF Center LTD</h6>
            </div>
            <div class="col-lg-4 mb-4">
                <video controls  poster="./images/v5.png" width="100% " class="rounded-2">
                    <source src="{{ asset('assets/frontend/videos/3145-166335957.mp4') }}" type="video/mp4">
                </video>
                <h6 class="txt-primary poppins-bold my-2">Qamar Hussain, Meadow Farm House LTD</h6>
            </div>
            <div class="col-lg-12 text-center mt-5">
                <h2 class="txt-primary text-center text-capitalize poppins-bold mb-4">If you want to grow your business</h2>
                <a href="{{ route('frontend.getQuotation') }}" class="btn bg-primary py-2 px-5 poppins-bold text-white"> Book your Appointment</a>

            </div>
        </div>
    </div>
</section>
