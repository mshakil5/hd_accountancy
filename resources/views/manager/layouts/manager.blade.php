<!DOCTYPE html>
<html lang="en">

@php
    $companyDetails = \App\Models\CompanyDetails::first();
@endphp

<head>
  <meta charset="utf-8">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>{{ $companyDetails->company_name ?? 'HD Accountancy' }}</title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <!-- Favicons -->
  <link rel="icon" href="{{ asset('images/company/' . $companyDetails->fav_icon) }}" />

  <!-- Google Fonts -->
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- Vendor CSS Files -->
    <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css')}}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/boxicons/css/boxicons.min.css')}}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/simple-datatables/style.css')}}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/select2/select2.min.css')}}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/datatables/dataTables.bootstrap4.min.css')}}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/datatables/jquery.dataTables.min.css')}}" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="{{ asset('assets/vendor/datatables/buttons.dataTables.min.css')}}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/toastr/toastr.css')}}" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="{{ asset('assets/css/style.css')}}" rel="stylesheet">
  <link href="{{ asset('assets/css/customize.css')}}" rel="stylesheet">

</head>

<body>

  <!-- header -->

  @include('manager.partials.header')
  
  <!-- header -->

  <!-- Sidebar -->
  
   @include('manager.partials.sidebar')
  <!-- Sidebar-->

  <!-- Main -->
  <main id="main" class="main mt-3">

      @yield('content')

  </main>
  <!--Main -->

  <!--Footer-->

  <!-- End Footer -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="{{ asset('assets/vendor/jquery/jquery.min.js')}}"></script>
  <script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
  <script src="{{ asset('assets/vendor/simple-datatables/simple-datatables.js')}}"></script>
  <script src="{{ asset('assets/vendor/php-email-form/validate.js')}}"></script>
  <script src="{{ asset('assets/vendor/select2/select2.min.js')}}"></script>
  <script src="{{ asset('assets/vendor/datatables/jquery.dataTables.min.js')}}"></script>
  <script src="{{ asset('assets/vendor/datatables/dataTables.bootstrap4.min.js')}}"></script>
  <script src="{{ asset('assets/vendor/moment/moment.min.js')}}"></script>
  <script src="{{ asset('assets/vendor/toastr/toastr.min.js')}}"></script>

  <!-- Template Main JS File -->
  <script src="{{ asset('assets/js/main.js') }}"></script>

  <script>
    function toggleSidebar() {
      document.getElementsByTagName('body').classlist.toggle('toggle-sidebar');
    }
  </script>

    <script>
    $(document).ready(function() {
      $('.select2').select2();
    });
  </script>

    <script>
        function updateDateTime() {
            var currentDate = new Date().toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            var currentTime = new Date().toLocaleTimeString(undefined, { hour12: false });
            document.getElementById('date').textContent = 'Date: ' + currentDate;
            document.getElementById('time').textContent = 'Time: ' + currentTime;
        }
        updateDateTime();
        setInterval(updateDateTime, 1000);
    </script>

  @yield('script')

</body>

</html>