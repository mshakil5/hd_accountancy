<header id="header" class=" site-main-header">
    <div class="container-fluid">
      <div class="row py-3">
        <div class="col-lg-4 d-flex align-items-end ps-4">
            <span class="text-light" id="date"></span>
        </div>
        <div class="col-lg-4">
          <div class="brand-box text-center bg-light py-2 rounded-4">
            <img src="{{ asset('assets/img/logo.png')}}" width="100" alt="" class="img-fluid">
          </div>
        </div>
        <div class="col-lg-4  d-flex align-items-end justify-content-end">
          <span class="text-light" id="time" ></span>
        </div>
      </div>
    </div>

    <div class="menuBar p-1 ps-4">
      <div class="row mx-0 align-items-center justify-content-between">
        <div class="col-auto">
          <i class="bi bi-list toggle-sidebar-btn curp text-black fs-4" onclick="toggleSidebar();"></i>
        </div>
        <div class="col-auto d-flex align-items-center">
          <i class="bi bi-clock fs-4 txt-theme mx-2" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#timeModal2" title="Log Out"></i> <span class="fw-bold txt-theme fs-6">Log Out</span>
          <i id="chatIcon" class="bi bi-chat-dots fs-4 txt-theme mx-2" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#chatModal" title="Messaging"></i> <span class="fw-bold txt-theme fs-6">Messaging</span>          
          <a>
            <i class="bi bi-person fs-4 txt-theme mx-2"></i> 
            <span class="fw-bold txt-theme fs-6">
              {{ auth()->user()->first_name }} {{ auth()->user()->last_name }}
            </span>
          </a>
        </div>
      </div>
    </div>
</header>

<style>
  #chatIcon {
    font-size: 2.2rem;
    transition: transform 0.3s ease, color 0.3s ease;
  }

  #chatIcon.new-message {
    color: #dc3545;
  }
</style>