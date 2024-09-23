<header class="site-header shadow-sm mb-1 fixed-top">
    <section class="container">
        <nav class="navbar navbar-expand-lg py-3">
            <div class="container-fluid">
                <a class="navbar-brand" href="{{ route('frontend.index') }}">
                    <img src="{{ asset('assets/frontend/images/logo.png') }}" width="160px" class="img-fluid" alt="logo">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                    aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0 navCustom">
                        <li class="nav-item">
                            <a class="nav-link active" aria-current="page" href="{{ route('frontend.index') }}">Home</a>
                        </li>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                                aria-expanded="false">
                                our service
                            </a>
                            <ul class="dropdown-menu border-0 shadow">
                                <li><a class="dropdown-item text-capitalize fw-bold " href="{{ route('frontend.services') }}#accounting-solution">accounting solution </a></li>
                                <li><a class="dropdown-item text-capitalize fw-bold " href="{{ route('frontend.services') }}#tax-solution">tax solution </a></li>
                                <li><a class="dropdown-item text-capitalize fw-bold " href="{{ route('frontend.services') }}#other-solution">other solution </a></li>

                            </ul>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('frontend.pricing') }}" class="nav-link">pricing</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('frontend.index') }}#about-us" class="nav-link">about us</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('frontend.contact') }}" class="nav-link">contact</a>
                        </li>
                    </ul>
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item bg-primary rounded-3">
                        <a href="{{ route('frontend.getQuotation') }}" class="nav-link text-white text-center fw-bold" aria-current="page">
                            <span class="fs-book-appointment">Book Appointment</span><br>
                            <span class="fs-phone-number">
                                <iconify-icon icon="charm:phone"></iconify-icon> 01484 508951
                            </span>
                        </a>
                    </li>

                    </ul>
                    <!-- <form class="d-flex" role="search">
                        <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search">
                        <button class="btn btn-outline-success" type="submit">Search</button>
                        </form> -->
                </div>
            </div>
        </nav>
    </section>
</header>