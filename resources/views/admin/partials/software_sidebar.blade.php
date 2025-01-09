<aside id="sidebar" class="sidebar">

  <ul class="sidebar-nav">

    <li class="nav-item {{ request()->routeIs('admin.home') ? 'active' : '' }}">
      <a class="nav-link collapsed" href="{{ route('admin.home') }}">
        <i class="bi bi-grid"></i>
        <span>Dashboard</span>
      </a>
    </li>

    @if (in_array('2', json_decode(Auth::user()->role->permission)))

    <form action="{{ route('toggle.sidebar') }}" method="POST">
      @csrf
      <input type="hidden" name="sidebar" value="1">
      <button type="submit" class="btn btn-secondary" style="background-color: #9bc653;">
          Switch to Web <i class="bi bi-arrow-right"></i>
      </button>
    </form>

    @endif

    @if (in_array('3', json_decode(Auth::user()->role->permission)))

    <li class="nav-item {{ request()->routeIs('allAdmin') ? 'active' : '' }}">
      <a class="nav-link collapsed" href="{{ route('allAdmin') }}">
        <i class="bi bi-shield"></i>
        <span>Admin</span>
      </a>
    </li>

    @endif

    @if (in_array('4', json_decode(Auth::user()->role->permission)))

    <li class="nav-item {{ request()->routeIs('allManager') ? 'active' : '' }}">
      <a class="nav-link collapsed" href="{{ route('allManager') }}">
        <i class="bi bi-briefcase"></i>
        <span>Manager</span>
      </a>
    </li>

    @endif

    @if (in_array('5', json_decode(Auth::user()->role->permission)))

    <li class="nav-item {{ request()->routeIs('allStaff', 'createStaff', 'staff.details') ? 'active' : '' }}">
      <a class="nav-link collapsed" href="{{ route('allStaff') }}">
        <i class="bi bi-person-badge"></i>
        <span>Staff</span>
      </a>
    </li>

    @endif

    @if (in_array('6', json_decode(Auth::user()->role->permission)))

    <li class="nav-item {{ request()->routeIs('allDepartment') ? 'active' : '' }}">
      <a class="nav-link collapsed" href="{{ route('allDepartment') }}">
        <i class="bi bi-building"></i>
        <span>Department</span>
      </a>
    </li>

    @endif

    @if (in_array('7', json_decode(Auth::user()->role->permission)) || in_array('8', json_decode(Auth::user()->role->permission)))

    <li class="nav-item {{ request()->routeIs('allClient','createClient','client.update.form','createNewClient') ? 'active' : '' }}">
      <a class="nav-link collapsed" href="{{ route('allClient') }}">
        <i class="bi bi-person"></i>
        <span>Client</span>
      </a>
    </li>

    @endif

    @if (in_array('9', json_decode(Auth::user()->role->permission)))

    <li class="nav-item {{ request()->routeIs('allClientType') ? 'active' : '' }}">
      <a class="nav-link collapsed" href="{{ route('allClientType') }}">
        <i class="bi bi-people"></i>
        <span>Client Type</span>
      </a>
    </li>

    @endif

    @if (in_array('10', json_decode(Auth::user()->role->permission)))

    <li class="nav-item {{ request()->routeIs('my.tasks') ? 'active' : '' }}">
      <a class="nav-link collapsed" href="{{ route('my.tasks') }}">
        <i class="bi bi-check-circle"></i>
        <span>My Tasks</span>
      </a>
    </li>

    @endif

    @if (in_array('11', json_decode(Auth::user()->role->permission)))

    <li class="nav-item {{ request()->routeIs('oneTimeJob.create') ? 'active' : '' }}">
      <a class="nav-link collapsed" href="{{ route('oneTimeJob.create') }}">
        <i class="bi bi-journal-text"></i>
        <span>One Time Job</span>
      </a>
    </li>

    @endif

    @if (in_array('12', json_decode(Auth::user()->role->permission)))

    <li class="nav-item {{ request()->routeIs('allService') ? 'active' : '' }}">
      <a class="nav-link collapsed" href="{{ route('allService') }}">
        <i class="bi bi-tools"></i>
        <span>Services</span>
      </a>
    </li>

    @endif

    @if (in_array('13', json_decode(Auth::user()->role->permission)))

    <li class="nav-item {{ request()->routeIs('prevLogStaffs','task.details.staff','allPrevLogStaffs') ? 'active' : '' }}">
      <a class="nav-link collapsed" href="{{ route('prevLogStaffs') }}">
        <i class="bi bi-calendar-check"></i>
        <span>Attendence Records</span>
      </a>
    </li>

    @endif

    @if (in_array('14', json_decode(Auth::user()->role->permission)))

    <li class="nav-item {{ request()->routeIs('holiday','createholiday','holidayReport','editHoliday') ? 'active' : '' }}">
      <a class="nav-link collapsed" href="{{ route('holiday') }}">
        <i class="bi bi-card-list"></i>
        <span>Holidays</span>
      </a>
    </li>

    @endif

    @if (in_array('15', json_decode(Auth::user()->role->permission)))

    <li class="nav-item {{ request()->routeIs('allHolidayType') ? 'active' : '' }}">
      <a class="nav-link collapsed" href="{{ route('allHolidayType') }}">
        <i class="bi bi-list"></i>
        <span>Holiday Types</span>
      </a>
    </li>

    @endif

    @if (in_array('16', json_decode(Auth::user()->role->permission)))

    <li class="nav-item {{ request()->routeIs('prorota*') ? 'active' : '' }}">
      <a class="nav-link collapsed" href="{{ route('prorota') }}">
        <i class="bi bi-box-arrow-in-right"></i>
        <span>Prorota</span>
      </a>
    </li>

    @endif

    @if (in_array('17', json_decode(Auth::user()->role->permission)))

    <li class="nav-item {{ (request()->is('admin/role*')) ? 'active' : '' }}">
      <a class="nav-link collapsed" href="{{ route('admin.role') }}">
        <i class="bi bi-person-lock"></i>
        <span>Manage Roles & Permissions</span>
      </a>
    </li>

    @endif

    <li class="nav-item">
        <a class="nav-link collapsed" href="{{ route('admin.logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form-2').submit();">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>
        <form id="logout-form-2" action="{{ route('admin.logout') }}" method="POST" class="d-none">
            @csrf
        </form>
    </li>

  </ul>

</aside>


<!-- Auto scroll -->
<script>
  document.addEventListener("DOMContentLoaded", function() {
    var activeItem = document.querySelector('.active');
    if (activeItem) {
      activeItem.scrollIntoView({
        behavior: 'smooth',
        block: 'center',
      });
    }
  });
</script>