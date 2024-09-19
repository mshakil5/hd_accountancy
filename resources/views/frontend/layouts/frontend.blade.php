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
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <meta property='og:title' content=""/>
        <meta property="og:image" content="{{ asset('images/company/' . $companyDetails->company_logo) }}" />
        <meta property='og:description' content=' ' />
        <meta property='og:url' content='' />
        <meta property='og:image:width' content='1200' />
        <meta property='og:image:height' content='627' />
        <meta property="og:type" content='website' />
        <title>{{ $companyDetails->company_name ?? 'HD Accountancy' }}</title>
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

        <!-- date picker -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

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
        <script src="{{ asset('assets/vendor/sweet-alert/sweetalert.min.js')}}"></script>

        <!-- date picker -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>

        <!-- Moment js -->
        <script src="{{ asset('assets/vendor/moment/moment.min.js')}}"></script>

        <!-- slick js -->
        @include('frontend.partials.slick-js')

        <!-- date picker -->
        @include('frontend.partials.date-picker')

        <!-- Faq answer show hide start -->
        <script>
            $(document).ready(function() {
                $('.faq-item').click(function() {
                    $(this).find('.faq-answer').slideToggle();
                    $(this).find('.faq-question').slideToggle();

                    $(this).find('.faq-icon i').toggleClass('fa-chevron-down fa-chevron-up');
                });
            });
        </script>
        <!-- Faq answer show hide end -->

        <!-- additional script -->
        @yield('script')

    </body>

</html>