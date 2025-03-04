<aside id="sidebar" class="sidebar">

    <ul class="sidebar-nav" id="sidebar-nav">

        <li class="nav-item {{ request()->routeIs('staff.home') ? 'active' : '' }}">
          <a class="nav-link collapsed" href="{{ route('staff.home') }}">
            <i class="bi bi-grid"></i>
            <span>Dashboard</span>
          </a>
        </li>

      <li class="nav-item {{ request()->routeIs('staff.profile.edit') ? 'active' : '' }}">
          <a class="nav-link collapsed" href="{{ route('staff.profile.edit') }}">
            <i class="bi bi-person"></i>
            <span>Profile</span>
          </a>
      </li>

        <li class="nav-item {{ request()->routeIs('allClientStaff') ? 'active' : '' }}">
          <a class="nav-link collapsed" href="{{ route('allClientStaff') }}">
            <i class="bi bi-person-badge"></i>
            <span>Client List</span>
          </a>
        </li>

        <li class="nav-item {{ request()->routeIs('allTaskList') ? 'active' : '' }}">
          <a class="nav-link collapsed" href="{{ route('allTaskList') }}">
            <i class="bi bi-check-square"></i>
            <span>Task List</span>
          </a>
        </li>

        <li class="nav-item {{ request()->routeIs('oneTimeJob.create.staff') ? 'active' : '' }}">
          <a class="nav-link collapsed" href="{{ route('oneTimeJob.create.staff') }}">
            <i class="bi bi-journal-text"></i>
            <span>One Time Job</span>
          </a>
        </li>

        <li class="nav-item {{ request()->routeIs('staff.holiday') ? 'active' : '' }}">
          <a class="nav-link collapsed" href="{{route('staff.holiday')}}">
            <i class="bi bi-card-list"></i>
            <span>Holiday</span>
          </a>
        </li>

        {{-- <li class="nav-item">
          <a class="nav-link collapsed" href="">
            <i class="bi bi-mic"></i>
            <span>Live Chat</span>
          </a>
      </li> --}}

    </ul>

  </aside>