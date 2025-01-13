<header id="header" class=" site-main-header">
    <div class="container-fluid">
      <div class="row py-3">
        <div class="col-lg-4 d-flex align-items-end ps-4">
          <span class="text-light" id="date"></span>
        </div>
        <div class="col-lg-4">
          <div class="brand-box text-center bg-light py-2 rounded-4">
            <img src="{{ asset('/assets/img/logo.png')}}" width="100" alt="" class="img-fluid">
          </div>
        </div>
        <div class="col-lg-4  d-flex align-items-end justify-content-end">
          <span class="text-light " id="time"></span>
        </div>
      </div>
    </div>

    <div class="menuBar p-1 ps-4">

      <div class="row mx-0">
        <div class="col-lg-6 d-flex justify-content-between align-items-center">
          <i class="bi bi-list toggle-sidebar-btn curp text-black fs-4" onclick="toggleSidebar();"></i>
          <div class="ms-5 d-none">
            <a href="{{ route('staff.home') }}" class="fw-bold me-5 txt-theme">Home</a>
            <a href="{{ route('allClientStaff') }}" class="fw-bold me-5 txt-theme">Client List</a>
            <a href="{{ route('allTaskList') }}" class="fw-bold me-5 txt-theme">Task List</a>
            <a href="{{ route('staff.holiday') }}" class="fw-bold me-5 txt-theme">Holiday</a>
          </div>
        </div>
        <div class="col-lg-6 justify-content-end d-flex align-items-center pe-3">
          <i class="bi bi-clock fs-4 txt-theme mx-2" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#timeModal1"></i>
          <i id="chatIcon" class="bi bi-chat-dots fs-4 txt-theme mx-2" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#chatModal"></i>
           <a href="{{ route('staff.profile.edit') }}">
            <span class="fw-bold txt-theme fs-6">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</span>
            <i class="bi bi-person fs-4 txt-theme mx-2"></i> 
          </a>
        </div>
      </div>
    </div>
  </header>
