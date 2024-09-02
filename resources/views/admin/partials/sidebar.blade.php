<aside id="sidebar" class="sidebar">

    <ul class="sidebar-nav" id="sidebar-nav">

      <li class="nav-item {{ request()->routeIs('admin.home') ? 'active' : '' }}">
            <a class="nav-link collapsed" href="{{ route('admin.home') }}">
                <i class="bi bi-grid"></i>
                <span>Dashboard</span>
            </a>
        </li>
      
      <li class="nav-item {{ request()->routeIs('allAdmin') ? 'active' : '' }}">
        <a class="nav-link collapsed" href="{{ route('allAdmin') }}">
            <i class="bi bi-shield"></i>
            <span>Admin</span>
        </a>
    </li>

      <li class="nav-item {{ request()->routeIs('allManager') ? 'active' : '' }}">
        <a class="nav-link collapsed" href="{{ route('allManager') }}">
            <i class="bi bi-briefcase"></i>
            <span>Manager</span>
        </a>
    </li>
      
    <li class="nav-item {{ request()->routeIs('allStaff', 'createStaff', 'staff.details') ? 'active' : '' }}">
        <a class="nav-link collapsed" href="{{ route('allStaff') }}">
            <i class="bi bi-person-badge"></i>
            <span>Staff</span>
        </a>
    </li>
    
      <li class="nav-item {{ request()->routeIs('allDepartment') ? 'active' : '' }}">
        <a class="nav-link collapsed" href="{{ route('allDepartment') }}">
            <i class="bi bi-building"></i>
            <span>Department</span>
        </a>
    </li>

      <li class="nav-item {{ request()->routeIs('allClient','createClient','client.update.form','createNewClient') ? 'active' : '' }}">
        <a class="nav-link collapsed" href="{{ route('allClient') }}">
            <i class="bi bi-person"></i>
            <span>Client</span>
        </a>
    </li>

      <li class="nav-item {{ request()->routeIs('allClientType') ? 'active' : '' }}">
        <a class="nav-link collapsed" href="{{ route('allClientType') }}">
            <i class="bi bi-people"></i>
            <span>Client Type</span>
        </a>
    </li>

      <li class="nav-item {{ request()->routeIs('allService') ? 'active' : '' }}">
        <a class="nav-link collapsed" href="{{ route('allService') }}">
            <i class="bi bi-tools"></i>
            <span>Services</span>
        </a>
    </li>

     <li class="nav-item {{ request()->routeIs('allWebService') ? 'active' : '' }}">
        <a class="nav-link collapsed" href="{{ route('allWebService') }}">
            <i class="bi bi-globe"></i>
            <span>Web Services</span>
        </a>
    </li>

       <li class="nav-item {{ request()->routeIs('weWorkImage') ? 'active' : '' }}">
        <a class="nav-link collapsed" href="{{ route('weWorkImage') }}">
            <i class="bi bi-images"></i>
            <span>We Work With Images</span>
        </a>
    </li>

    <li class="nav-item {{ request()->routeIs('homepageOurValues') || request()->routeIs('homepageIntro') ? 'menu-open' : '' }}">
      <a class="nav-link collapsed {{ request()->routeIs('homepageOurValues') || request()->routeIs('homepageIntro') ? 'active' : '' }}" href="#" data-bs-toggle="collapse" data-bs-target="#homepageDropdown" aria-expanded="{{ request()->routeIs('homepageOurValues') || request()->routeIs('homepageIntro') ? 'true' : 'false' }}">
          <i class="bi bi-house"></i>
          <span>Homepage</span>
          <i class="bi bi-chevron-down"></i>
      </a>

      <ul id="homepageDropdown" class="collapse list-unstyled {{ request()->routeIs('homepageOurValues') || request()->routeIs('homepageIntro') ? 'show' : '' }}">
          <li class="nav-item {{ request()->routeIs('homepageOurValues') ? 'active' : '' }}">
              <a class="nav-link" href="{{ route('homepageOurValues') }}">
                  <i class="bi bi-heart"></i>
                  <span>Our Values</span>
              </a>
          </li>
          <li class="nav-item {{ request()->routeIs('homepageIntro') ? 'active' : '' }}">
              <a class="nav-link" href="{{ route('homepageIntro') }}">
                  <i class="bi bi-info-circle"></i>
                  <span>Intro</span>
              </a>
          </li>
      </ul>
  </li>

  <li class="nav-item {{ request()->routeIs('allContactMessage*') || request()->routeIs('webContact') ? 'menu-open' : '' }}">
      <a class="nav-link collapsed {{ request()->routeIs('allContactMessage*') || request()->routeIs('webContact') ? 'active' : '' }}" href="#" data-bs-toggle="collapse" data-bs-target="#contactMessageDropdown" aria-expanded="{{ request()->routeIs('allContactMessage*') || request()->routeIs('webContact') ? 'true' : 'false' }}">
          <i class="bi bi-envelope"></i>
          <span>Contact</span>
          <i class="bi bi-chevron-down"></i>
      </a>

      <ul id="contactMessageDropdown" class="collapse list-unstyled {{ request()->routeIs('allContactMessage*') || request()->routeIs('webContact') ? 'show' : '' }}">
          <li class="nav-item {{ request()->routeIs('allContactMessage') ? 'active' : '' }}">
              <a class="nav-link" href="{{ route('allContactMessage') }}">
                  <i class="bi bi-file-text"></i>
                  <span>Contact Messages</span>
              </a>
          </li>
          <li class="nav-item {{ request()->routeIs('webContact') ? 'active' : '' }}">
              <a class="nav-link" href="{{ route('webContact') }}">
                  <i class="bi bi-pencil"></i>
                  <span>Contact Page</span>
              </a>
          </li>
      </ul>
  </li>

    <li class="nav-item {{ request()->routeIs('servicepageAccounting') || request()->routeIs('servicepageTaxSolution') || request()->routeIs('servicepageOtherSolution') || request()->routeIs('servicepageBusinessStartup') || request()->routeIs('servicepageCompanySecretarial') || request()->routeIs('bankruptcyAndLiquidation') ? 'menu-open' : '' }}">
    <a class="nav-link collapsed {{ request()->routeIs('servicepageAccounting') || request()->routeIs('servicepageTaxSolution') || request()->routeIs('servicepageOtherSolution') || request()->routeIs('servicepageBusinessStartup') || request()->routeIs('servicepageCompanySecretarial') || request()->routeIs('bankruptcyAndLiquidation') ? 'active' : '' }}" href="#" data-bs-toggle="collapse" data-bs-target="#servicePagesDropdown" aria-expanded="{{ request()->routeIs('servicepageAccounting') || request()->routeIs('servicepageTaxSolution') || request()->routeIs('servicepageOtherSolution') || request()->routeIs('servicepageBusinessStartup') || request()->routeIs('servicepageCompanySecretarial') || request()->routeIs('bankruptcyAndLiquidation') ? 'true' : 'false' }}">
        <i class="bi bi-file-text"></i>
        <span>Service Pages</span>
        <i class="bi bi-chevron-down"></i>
    </a>

    <ul id="servicePagesDropdown" class="collapse list-unstyled {{ request()->routeIs('servicepageAccounting') || request()->routeIs('servicepageTaxSolution') || request()->routeIs('servicepageOtherSolution') || request()->routeIs('servicepageBusinessStartup') || request()->routeIs('servicepageCompanySecretarial') || request()->routeIs('bankruptcyAndLiquidation') ? 'show' : '' }}">
        <li class="nav-item {{ request()->routeIs('servicepageAccounting') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('servicepageAccounting') }}">
                <i class="bi bi-file-text"></i>
                <span>Accounting</span>
            </a>
        </li>
        <li class="nav-item {{ request()->routeIs('servicepageTaxSolution') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('servicepageTaxSolution') }}">
                <i class="bi bi-file-text"></i>
                <span>Tax Solution</span>
            </a>
        </li>
        <li class="nav-item {{ request()->routeIs('servicepageOtherSolution') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('servicepageOtherSolution') }}">
                <i class="bi bi-file-text"></i>
                <span>Other Solution</span>
            </a>
        </li>
        <li class="nav-item {{ request()->routeIs('servicepageBusinessStartup') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('servicepageBusinessStartup') }}">
                <i class="bi bi-file-text"></i>
                <span>Business Startup</span>
            </a>
        </li>
        <li class="nav-item {{ request()->routeIs('servicepageCompanySecretarial') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('servicepageCompanySecretarial') }}">
                <i class="bi bi-file-text"></i>
                <span>Company Secretarial</span>
            </a>
        </li>
        <li class="nav-item {{ request()->routeIs('bankruptcyAndLiquidation') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('bankruptcyAndLiquidation') }}">
                <i class="bi bi-file-text"></i>
                <span>Bankruptcy and Liquidation</span>
            </a>
        </li>
    </ul>
  </li>

    <li class="nav-item {{ request()->routeIs('prevLogStaffs','task.details.staff','allPrevLogStaffs') ? 'active' : '' }}">
        <a class="nav-link collapsed" href="{{ route('prevLogStaffs') }}">
            <i class="bi bi-calendar-check"></i>
            <span>Attendence Records</span>
        </a>
    </li>

      {{-- <li class="nav-item">
        <a class="nav-link collapsed" href="">
          <i class="bi bi-clock"></i>
          <span>Attendence</span>
        </a>
      </li> --}}

      <li class="nav-item {{ request()->routeIs('holiday','createholiday','holidayReport','editHoliday') ? 'active' : '' }}">
        <a class="nav-link collapsed" href="{{ route('holiday') }}">
          <i class="bi bi-card-list"></i>
          <span>Holidays</span>
        </a>
      </li>

      <li class="nav-item {{ request()->routeIs('allHolidayType') ? 'active' : '' }}">
        <a class="nav-link collapsed" href="{{ route('allHolidayType') }}">
          <i class="bi bi-list"></i>
          <span>Holiday Types</span>
        </a>
      </li>

      <li class="nav-item {{ request()->routeIs('prorota*') ? 'active' : '' }}">
        <a class="nav-link collapsed" href="{{ route('prorota') }}">
          <i class="bi bi-box-arrow-in-right"></i>
          <span>Prorota</span>
        </a>
      </li>

      <li class="nav-item {{ request()->routeIs('allSoftCode') ? 'active' : '' }}">
        <a class="nav-link collapsed" href="{{ route('allSoftCode') }}">
          <i class="bi bi-code"></i>
          <span>Soft Code</span>
        </a>
      </li>

      <li class="nav-item {{ request()->routeIs('allMaster') ? 'active' : '' }}">
        <a class="nav-link collapsed" href="{{ route('allMaster') }}">
          <i class="bi bi-list"></i>
          <span>Master</span>
        </a>
      </li>

      <li class="nav-item {{ request()->routeIs('allTimeslot') ? 'active' : '' }}">
        <a class="nav-link collapsed" href="{{ route('allTimeslot') }}">
          <i class="bi bi-list"></i>
          <span>Time slot</span>
        </a>
      </li>

      <li class="nav-item {{ request()->routeIs('admin.companyDetail') ? 'active' : '' }}">
        <a class="nav-link collapsed" href="{{ route('admin.companyDetail') }}">
          <i class="bi bi-building"></i>
          <span>Company Details</span>
        </a>
      </li>

      {{--  <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#icons-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-gem"></i><span>Reports</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="icons-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a href="">
              <i class="bi bi-circle"></i><span>option</span>
            </a>
          </li>
        </ul>
       </li> --}}

      <li class="nav-item">
        <a class="nav-link collapsed" href="{{ route('logout') }}"  onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
          <i class="bi bi-box-arrow-right"></i>
          <span>Logout</span>
        </a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
            @csrf
        </form>
      </li>

    </ul>

</aside>