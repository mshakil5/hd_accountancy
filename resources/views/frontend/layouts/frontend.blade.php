<!doctype html>
    <html lang="en">

    @php
        $companyDetails = \App\Models\CompanyDetails::first();
    @endphp

    <head>
        <!-- seo activities -->
        <meta charset="utf-8">
        <meta name="google-site-verification" content="b3JBEB_Tp6NzGBZBo-wCS3L3RNdS1WNSiqZdCdt0gDw" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>@yield('meta_title', 'HD Accountancy')</title>

        {{-- <meta property="og:title" content="@yield('meta_title', 'HD Accountancy')" /> --}}
        <meta property="og:description" content="@yield('meta_description', 'HD Accountancy')" />
        <meta name="keywords" content="@yield('meta_keywords', 'HD Accountancy')" />
        <meta property="og:image" content="@yield('meta_image', asset('images/company/' . $companyDetails->company_logo))" />
        <meta property="og:url" content="{{ request()->url() }}" />
        <meta property='og:image:width' content='1200' />
        <meta property='og:image:height' content='627' />
        <meta property="og:type" content='website' />
        <link rel="icon" href="{{ asset('images/company/' . $companyDetails->fav_icon) }}" />
        <!-- css -->
        <link href="{{ asset('assets/frontend/css/bootstrap-5.2.3.min.css') }}" rel="stylesheet">
        <link href="{{ asset('assets/frontend/css/app.css') }}" rel="stylesheet">
        <link href="{{ asset('assets/frontend/css/animate.min.css') }}" rel="stylesheet">

        <!-- slick css -->
        <link href="{{ asset('assets/frontend/css/slick.css') }}" rel="stylesheet">
        <link href="{{ asset('assets/frontend/css/slick-theme.css') }}" rel="stylesheet">

        <!-- popup css -->
        <link href="{{ asset('assets/frontend/css/popup.css') }}" rel="stylesheet">

        <link href="{{ asset('assets/vendor/toastr/toastr.css') }}" rel="stylesheet">

        <!-- date picker -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

        @include('frontend.partials.date-picker-css')

        <meta name="google-site-verification" content="B66_NizNd9PxdegluYuSlPYKQVJDPFfFY5TKA2RmdTI" />

        <!-- Google tag (gtag.js) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=G-NH9VPXW8Z0">
        </script>
        <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', 'G-NH9VPXW8Z0');
        </script>

    </head>

    <body>

        <!-- site header start -->
        @include('frontend.partials.header')
        <!-- site header end -->

        <!-- Main content start -->
        @yield('content')
        <!-- Main content end -->

        <!-- site footer start -->
        @include('frontend.partials.footer')
        <!-- site footer End -->

        <!-- Package js -->
        <script src="{{ asset('assets/frontend/js/bootstrap-5.2.3-bundle.min.js')}}"></script>
        <script src="{{ asset('assets/frontend/js/jquery-v2.1.3.min.js')}}"></script>
        <script src="{{ asset('assets/frontend/js/jquery-ui.min.js')}}"></script>
        <script src="{{ asset('assets/frontend/js/slick.min.js')}}"></script>
        <script src="{{ asset('assets/frontend/js/app.js')}}"></script>
        <script src="{{ asset('assets/frontend/js/wow.min.js')}}"></script>
        <script src="{{ asset('assets/frontend/js/iconify.min.js')}}"></script>
        <script src="{{ asset('assets/vendor/toastr/toastr.min.js')}}"></script>

        <!-- date picker -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>

        <!-- Moment js -->
        <script src="{{ asset('assets/vendor/moment/moment.min.js')}}"></script>

        <!-- Lazy sizes -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/lazysizes/5.3.0/lazysizes.min.js" async></script>

        <!-- recaptcha -->
        <script src="https://www.google.com/recaptcha/enterprise.js" async defer></script>

        <!-- slick js -->
        @include('frontend.partials.slick-js')

        <!-- date picker -->
        @include('frontend.partials.date-picker')

        <!-- Lazy size start -->
        @include('frontend.partials.lazy-sizes')

        <!-- Tawk.to  -->
        @include('frontend.partials.tawk-to')

        <!-- additional script -->
        @yield('script')

    </body>

</html>