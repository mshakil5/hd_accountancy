<header class="site-header shadow-sm mb-1">
    <section class="container">
        <nav class="navbar navbar-expand-lg py-3">
            <div class="container-fluid">
                <a class="navbar-brand" href="index.php">
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
                            <a class="nav-link active" aria-current="page" href="index.php">Home</a>
                        </li>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                                aria-expanded="false">
                                our service
                            </a>
                            <ul class="dropdown-menu border-0 shadow">
                                <li><a class="dropdown-item text-capitalize fw-bold " href="pricing.php">accounting solution </a></li>
                                <li><a class="dropdown-item text-capitalize fw-bold " href="#">tax solution </a></li>
                                <li><a class="dropdown-item text-capitalize fw-bold " href="#">other solution </a></li>

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
                        <li class="nav-item bg-primary box-hover rounded-3  ">
                            <a href="{{ route('frontend.getQuotation') }}" class="nav-link text-white text-center fw-bold fs-6 px-4" aria-current="page">Book Appointment <br>
                                <span class="fs-5 d-flex gap-1 justify-content-center align-items-center">
                                    <iconify-icon icon="charm:phone"></iconify-icon> 01484 508951</span>
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