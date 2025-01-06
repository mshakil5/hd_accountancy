<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Login</title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <!-- Favicons -->
  <link href=" {{ asset('assets/img/favicon.png')}} " rel="icon">
  <link href="{{ asset('assets/img/apple-touch-icon.png')}}" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css')}}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/boxicons/css/boxicons.min.css')}}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/simple-datatables/style.css')}}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/select2/select2.min.css')}}" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="{{ asset('assets/css/style.css')}}" rel="stylesheet">
  <link href="{{ asset('assets/css/customize.css')}}" rel="stylesheet">

</head>

<body>

  <!-- header -->

  <header id="header" class=" site-main-header">
    <div class="container-fluid">
      <div class="row py-3">
        <div class="col-lg-4 d-flex align-items-end ps-4">
          <span class="text-light" id="date"></span>
        </div>
        <div class="col-lg-4">
          <div class="brand-box text-center bg-light py-2 rounded-4">
            <img src="./assets/img/logo.png" width="150" alt="" class="img-fluid">
          </div>
        </div>
        <div class="col-lg-4  d-flex align-items-end justify-content-end">
          <span class="text-light" id="time"></span>
        </div>
      </div>
    </div>

    <div class="menuBar p-1 ps-4">
      <i class="bi bi-list toggle-sidebar-btn curp text-black fs-4 opacity-0" onclick="toggleSidebar();"></i>

    </div>
  </header>
  
  <!-- header -->

  <!-- Main -->
  <main id="main" class="main mt-3 ms-0">

      @yield('content')

  </main>
  <!--Main -->

  <!-- Vendor JS Files -->
  <script src="{{ asset('assets/vendor/jquery/jquery.min.js')}}"></script>
  <script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
  <script src="{{ asset('assets/vendor/simple-datatables/simple-datatables.js')}}"></script>
  <!-- <script src="{{ asset('assets/vendor/tinymce/tinymce.min.js')}}"></script> -->
  <script src="{{ asset('assets/vendor/php-email-form/validate.js')}}"></script>
  <script src="{{ asset('assets/vendor/select2/select2.min.js')}}"></script>

  <!-- Template Main JS File -->
  <script src="{{ asset('assets/js/main.js') }}"></script>

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