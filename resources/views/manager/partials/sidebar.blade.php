<aside id="sidebar" class="sidebar">

    <ul class="sidebar-nav" id="sidebar-nav">

        <li class="nav-item {{ request()->routeIs('manager.home') ? 'active' : '' }}">
          <a class="nav-link collapsed" href="{{ route('manager.home') }}">
            <i class="bi bi-grid"></i>
            <span>Dashboard</span>
          </a>
        </li>

      <li class="nav-item {{ request()->routeIs('manager.profile.edit') ? 'active' : '' }}">
          <a class="nav-link collapsed" href="{{ route('manager.profile.edit') }}">
            <i class="bi bi-person"></i>
            <span>Profile</span>
          </a>
      </li>

        <li class="nav-item {{ request()->routeIs('allClientManager') ? 'active' : '' }}">
          <a class="nav-link collapsed" href="{{ route('allClientManager') }}">
            <i class="bi bi-person-badge"></i>
            <span>Client List</span>
          </a>
        </li>

        <li class="nav-item {{ request()->routeIs('allTaskListManager') ? 'active' : '' }}">
          <a class="nav-link collapsed" href="{{ route('allTaskListManager') }}">
            <i class="bi bi-check-square"></i>
            <span>Task List</span>
          </a>
        </li>

        <li class="nav-item {{ request()->routeIs('oneTimeJob.create.manager') ? 'active' : '' }}">
          <a class="nav-link collapsed" href="{{ route('oneTimeJob.create.manager') }}">
            <i class="bi bi-journal-text"></i>
            <span>One Time Job</span>
          </a>
        </li>

        <li class="nav-item {{ request()->routeIs('manager.holiday') ? 'active' : '' }}">
          <a class="nav-link collapsed" href="{{route('manager.holiday')}}">
            <i class="bi bi-card-list"></i>
            <span>Holiday</span>
          </a>
        </li>

        {{-- <li class="nav-item">
          <a class="nav-link collapsed" href="">
            <i class="bi bi-mic"></i>
            <span>Live Chat</span>
          </a>
      </li>  --}}

       {{--  <li class="nav-item">
        <a class="nav-link collapsed" href="{{ route('logout') }}"  onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
          <i class="bi bi-box-arrow-right"></i>
          <span>Logout</span>
        </a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
            @csrf
        </form>
      </li>  --}}

    </ul>

  </aside>