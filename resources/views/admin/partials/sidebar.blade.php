<aside id="sidebar" class="sidebar">

    <ul class="sidebar-nav">

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

      <li class="nav-item {{ request()->routeIs('prevLogStaffs','task.details.staff','allPrevLogStaffs') ? 'active' : '' }}">
          <a class="nav-link collapsed" href="{{ route('prevLogStaffs') }}">
              <i class="bi bi-calendar-check"></i>
              <span>Attendence Records</span>
          </a>
      </li>

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

      <li class="nav-item {{ request()->routeIs('admin.companyDetail') ? 'active' : '' }}">
        <a class="nav-link collapsed" href="{{ route('admin.companyDetail') }}">
          <i class="bi bi-building"></i>
          <span>Company Details</span>
        </a>
      </li>

      <br>

      <li class="nav-item {{ request()->routeIs('homepageOurValues') || request()->routeIs('homepageIntro') || request()->routeIs('weWorkImage') || request()->routeIs('allClientTestimonial') || request()->routeIs('allCaseStudies') || request()->routeIs('allCaseStudies') || request()->routeIs('allLatestInsight') ? 'menu-open' : '' }}">
          <a class="nav-link collapsed {{ request()->routeIs('homepageOurValues') || request()->routeIs('homepageIntro') || request()->routeIs('weWorkImage') || request()->routeIs('allClientTestimonial') || request()->routeIs('allCaseStudies') || request()->routeIs('allCaseStudies') || request()->routeIs('allLatestInsight') ? 'active' : '' }}" href="#" data-bs-toggle="collapse" data-bs-target="#homepageDropdown" aria-expanded="{{ request()->routeIs('homepageOurValues') || request()->routeIs('homepageIntro') || request()->routeIs('weWorkImage') || request()->routeIs('allClientTestimonial') || request()->routeIs('allCaseStudies') || request()->routeIs('allClientReview') || request()->routeIs('allLatestInsight') ? 'true' : 'false' }}">
              <i class="bi bi-house"></i>
              <span>Homepage</span>
              <i class="bi bi-chevron-down"></i>
          </a>

          <ul id="homepageDropdown" class="collapse list-unstyled {{ request()->routeIs('homepageOurValues') || request()->routeIs('homepageIntro') || request()->routeIs('weWorkImage') || request()->routeIs('allClientTestimonial') || request()->routeIs('allCaseStudies') || request()->routeIs('allBusinessService') || request()->routeIs('allBusinessValue') || request()->routeIs('allClientReview') || request()->routeIs('allLatestInsight') ? 'show' : '' }}">
              <li class="nav-item {{ request()->routeIs('homepageIntro') ? 'active' : '' }}">
                  <a class="nav-link" href="{{ route('homepageIntro') }}">
                      <i class="bi bi-info-circle"></i>
                      <span>Intro</span>
                  </a>
              </li>
              <li class="nav-item {{ request()->routeIs('weWorkImage') ? 'active' : '' }}">
                  <a class="nav-link" href="{{ route('weWorkImage') }}">
                      <i class="bi bi-images"></i>
                      <span>We Work With Images</span>
                  </a>
              </li>
              <li class="nav-item {{ request()->routeIs('homepageOurValues') ? 'active' : '' }}">
                  <a class="nav-link" href="{{ route('homepageOurValues') }}">
                      <i class="bi bi-heart"></i>
                      <span>Our Values</span>
                  </a>
              </li>
              <li class="nav-item {{ request()->routeIs('allBusinessService') ? 'active' : '' }}">
                  <a class="nav-link" href="{{ route('allBusinessService') }}">
                      <i class="bi bi-briefcase"></i>
                      <span>Business Service</span>
                  </a>
              </li>
              <li class="nav-item {{ request()->routeIs('allBusinessValue') ? 'active' : '' }}">
                  <a class="nav-link" href="{{ route('allBusinessValue') }}">
                      <i class="bi bi-pie-chart"></i>
                      <span>Business Values</span>
                  </a>
              </li>
              <li class="nav-item {{ request()->routeIs('allClientTestimonial') ? 'active' : '' }}">
                  <a class="nav-link" href="{{ route('allClientTestimonial') }}">
                      <i class="bi bi-chat-quote"></i>
                      <span>Client Testimonials</span>
                  </a>
              </li>
              <li class="nav-item {{ request()->routeIs('allClientReview') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('allClientReview') }}">
                    <i class="bi bi-star"></i>
                    <span>Google Review</span>
                </a>
              </li>
              <li class="nav-item {{ request()->routeIs('allCaseStudies') ? 'active' : '' }}">
                  <a class="nav-link" href="{{ route('allCaseStudies') }}">
                      <i class="bi bi-file-text"></i>
                      <span>Case Studies</span>
                  </a>
              </li>
              <li class="nav-item {{ request()->routeIs('allLatestInsight') ? 'active' : '' }}">
                  <a class="nav-link" href="{{ route('allLatestInsight') }}">
                      <i class="bi bi-lightbulb"></i>
                      <span>Latest Insights</span>
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

        <ul id="servicePagesDropdown" class="collapse list-unstyled {{ request()->routeIs('servicepageAccounting') || request()->routeIs('allAccoutingSolution') || request()->routeIs('allTax') || request()->routeIs('servicepageTaxSolution') || request()->routeIs('servicepageOtherSolution') || request()->routeIs('servicepageBusinessStartup') || request()->routeIs('servicepageCompanySecretarial') || request()->routeIs('bankruptcyAndLiquidation') ? 'show' : '' }}">
            <li class="nav-item {{ request()->routeIs('servicepageAccounting') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('servicepageAccounting') }}">
                    <i class="bi bi-file-text"></i>
                    <span>Accounting Solution</span>
                </a>
            </li>
            <li class="nav-item {{ request()->routeIs('allAccoutingSolution') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('allAccoutingSolution') }}">
                    <i class="bi bi-file-text"></i>
                    <span>Accounting Solution Offers</span>
                </a>
            </li>
            <li class="nav-item {{ request()->routeIs('servicepageTaxSolution') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('servicepageTaxSolution') }}">
                    <i class="bi bi-file-text"></i>
                    <span>Tax Solution</span>
                </a>
            </li>
            <li class="nav-item {{ request()->routeIs('allTax') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('allTax') }}">
                    <i class="bi bi-file-text"></i>
                    <span>Tax Solution Offers</span>
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

      <li class="nav-item {{ request()->routeIs('allPackage') || request()->routeIs('allPackageFeature') ? 'menu-open' : '' }}">
        <a class="nav-link collapsed {{ request()->routeIs('allPackage') || request()->routeIs('allPackageFeature') ? 'active' : '' }}" href="#" data-bs-toggle="collapse" data-bs-target="#packageDropdown" aria-expanded="{{ request()->routeIs('allPackage') || request()->routeIs('allPackageFeature') ? 'true' : 'false' }}">
            <i class="bi bi-box"></i>
            <span>Package</span>
            <i class="bi bi-chevron-down"></i>
        </a>

        <ul id="packageDropdown" class="collapse list-unstyled {{ request()->routeIs('allPackage') || request()->routeIs('allPackageFeature') || request()->routeIs('package-turnover') ? 'show' : '' }}">
            <li class="nav-item {{ request()->routeIs('allPackage') || request()->routeIs('package-turnover') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('allPackage') }}">
                    <i class="bi bi-box"></i>
                    <span>All Packages</span>
                </a>
            </li>
            <li class="nav-item {{ request()->routeIs('allPackageFeature') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('allPackageFeature') }}">
                    <i class="bi bi-list-check"></i>
                    <span>Package Features</span>
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
              <li class="nav-item {{ request()->routeIs('webContact') ? 'active' : '' }}">
                  <a class="nav-link" href="{{ route('webContact') }}">
                      <i class="bi bi-pencil"></i>
                      <span>Contact Page</span>
                  </a>
              </li>
              <li class="nav-item {{ request()->routeIs('allContactMessage') ? 'active' : '' }}">
                  <a class="nav-link" href="{{ route('allContactMessage') }}">
                      <i class="bi bi-file-text"></i>
                      <span>Contact Messages</span>
                  </a>
              </li>       
          </ul>
      </li>

      <li class="nav-item {{ request()->routeIs('quotationPage') || request()->routeIs('allQuotationList') ? 'menu-open' : '' }}">
        <a class="nav-link collapsed {{ request()->routeIs('quotationPage') || request()->routeIs('allQuotationList') ? 'active' : '' }}" href="#" data-bs-toggle="collapse" data-bs-target="#quotationDropdown" aria-expanded="{{ request()->routeIs('quotationPage') || request()->routeIs('allQuotationList') ? 'true' : 'false' }}">
          <i class="bi bi-file-text"></i>
          <span>Quotation</span>
          <i class="bi bi-chevron-down"></i>
        </a>

        <ul id="quotationDropdown" class="collapse list-unstyled {{ request()->routeIs('quotationPage') || request()->routeIs('allQuotationList') ? 'show' : '' }}">
          <li class="nav-item {{ request()->routeIs('quotationPage') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('quotationPage') }}">
              <i class="bi bi-file-text"></i>
              <span>Quotation Page</span>
            </a>
          </li>
          <li class="nav-item {{ request()->routeIs('allQuotationList') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('allQuotationList') }}">
              <i class="bi bi-list-ul"></i>
              <span>Quotation List</span>
            </a>
          </li>
        </ul>
      </li>

      <li class="nav-item {{ request()->routeIs('careerPage') || request()->routeIs('allCareerList') ? 'menu-open' : '' }}">
        <a class="nav-link collapsed {{ request()->routeIs('careerPage') || request()->routeIs('allCareerList') ? 'active' : '' }}" href="#" data-bs-toggle="collapse" data-bs-target="#careerDropdown" aria-expanded="{{ request()->routeIs('careerPage') || request()->routeIs('allCareerList') ? 'true' : 'false' }}">
          <i class="bi bi-briefcase"></i>
          <span>Career</span>
          <i class="bi bi-chevron-down"></i>
        </a>

        <ul id="careerDropdown" class="collapse list-unstyled {{ request()->routeIs('careerPage') || request()->routeIs('allCareerList') ? 'show' : '' }}">
          <li class="nav-item {{ request()->routeIs('careerPage') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('careerPage') }}">
              <i class="bi bi-briefcase"></i>
              <span>Career Page</span>
            </a>
          </li>
          <li class="nav-item {{ request()->routeIs('allCareerList') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('allCareerList') }}">
              <i class="bi bi-list-ul"></i>
              <span>Career List</span>
            </a>
          </li>
        </ul>
      </li>

      <li class="nav-item {{ request()->routeIs('allTimeslot') || request()->routeIs('allBookingList') ? 'menu-open' : '' }}">
        <a class="nav-link collapsed {{ request()->routeIs('allTimeslot') || request()->routeIs('allBookingList') ? 'active' : '' }}" href="#" data-bs-toggle="collapse" data-bs-target="#bookingDropdown" aria-expanded="{{ request()->routeIs('allTimeslot') || request()->routeIs('allBookingList') ? 'true' : 'false' }}">
          <i class="bi bi-calendar"></i>
          <span>Booking</span>
          <i class="bi bi-chevron-down"></i>
        </a>

        <ul id="bookingDropdown" class="collapse list-unstyled {{ request()->routeIs('allTimeslot') || request()->routeIs('allBookingList') ? 'show' : '' }}">
          <li class="nav-item {{ request()->routeIs('allTimeslot') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('allTimeslot') }}">
              <i class="bi bi-list"></i>
              <span>Time Slot</span>
            </a>
          </li>
          <li class="nav-item {{ request()->routeIs('allBookingList') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('allBookingList') }}">
              <i class="bi bi-calendar"></i>
              <span>Schedule List</span>
            </a>
          </li>
        </ul>
      </li>

      <li class="nav-item {{ request()->routeIs('allOurTeam') ? 'active' : '' }}">
          <a class="nav-link collapsed" href="{{ route('allOurTeam') }}">
              <i class="bi bi-people"></i>
              <span>Our Team</span>
          </a>
      </li> 

      <li class="nav-item {{ request()->routeIs('allFaq') ? 'active' : '' }}">
        <a class="nav-link collapsed" href="{{ route('allFaq') }}">
            <i class="fas fa-question-circle"></i>
            <span>FAQ Questions & Answers</span>
        </a>
      </li>

      <li class="nav-item {{ request()->routeIs('homeMeta') || request()->routeIs('serviceMeta') || request()->routeIs('packageMeta') || request()->routeIs('contactMeta') || request()->routeIs('getQuotationMeta') || request()->routeIs('careerMeta') || request()->routeIs('ourTeamMeta') || request()->routeIs('faqMeta') || request()->routeIs('privacyMeta') || request()->routeIs('termsMeta') ? 'menu-open' : '' }}">
        <a class="nav-link collapsed {{ request()->routeIs('homeMeta') || request()->routeIs('serviceMeta') || request()->routeIs('packageMeta') || request()->routeIs('contactMeta') || request()->routeIs('getQuotationMeta') || request()->routeIs('careerMeta') || request()->routeIs('ourTeamMeta') || request()->routeIs('faqMeta') || request()->routeIs('privacyMeta') || request()->routeIs('termsMeta') ? 'active' : '' }}" href="#" data-bs-toggle="collapse" data-bs-target="#metaDropdown" aria-expanded="{{ request()->routeIs('homeMeta') || request()->routeIs('serviceMeta') || request()->routeIs('packageMeta') || request()->routeIs('contactMeta') || request()->routeIs('getQuotationMeta') || request()->routeIs('careerMeta') || request()->routeIs('ourTeamMeta') || request()->routeIs('faqMeta') || request()->routeIs('privacyMeta') || request()->routeIs('termsMeta') ? 'true' : 'false' }}">
          <i class="bi bi-folder"></i>
          <span>Meta Data</span>
          <i class="bi bi-chevron-down"></i>
        </a>

        <ul id="metaDropdown" class="collapse list-unstyled {{ request()->routeIs('homeMeta') || request()->routeIs('serviceMeta') || request()->routeIs('packageMeta') || request()->routeIs('contactMeta') || request()->routeIs('getQuotationMeta') || request()->routeIs('careerMeta') || request()->routeIs('ourTeamMeta') || request()->routeIs('faqMeta') || request()->routeIs('privacyMeta') || request()->routeIs('termsMeta') ? 'show' : '' }}">
          <li class="nav-item {{ request()->routeIs('homeMeta') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('homeMeta') }}">
              <i class="bi bi-house"></i>
              <span>Home Page</span>
            </a>
          </li>
          <li class="nav-item {{ request()->routeIs('serviceMeta') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('serviceMeta') }}">
              <i class="bi bi-gear"></i>
              <span>Service Page</span>
            </a>
          </li>
          <li class="nav-item {{ request()->routeIs('packageMeta') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('packageMeta') }}">
              <i class="bi bi-box"></i>
              <span>Package Page</span>
            </a>
          </li>
          <li class="nav-item {{ request()->routeIs('contactMeta') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('contactMeta') }}">
              <i class="bi bi-envelope"></i>
              <span>Contact Page</span>
            </a>
          </li>
          <li class="nav-item {{ request()->routeIs('getQuotationMeta') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('getQuotationMeta') }}">
              <i class="bi bi-file-text"></i>
              <span>Get Quotation Page</span>
            </a>
          </li>
          <li class="nav-item {{ request()->routeIs('careerMeta') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('careerMeta') }}">
              <i class="bi bi-briefcase"></i>
              <span>Career Page</span>
            </a>
          </li>
          <li class="nav-item {{ request()->routeIs('ourTeamMeta') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('ourTeamMeta') }}">
              <i class="bi bi-people"></i>
              <span>Our Team Page</span>
            </a>
          </li>
          <li class="nav-item {{ request()->routeIs('faqMeta') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('faqMeta') }}">
              <i class="bi bi-question-circle"></i>
              <span>FAQ Page</span>
            </a>
          </li>
          <li class="nav-item {{ request()->routeIs('privacyMeta') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('privacyMeta') }}">
              <i class="bi bi-shield"></i>
              <span>Privacy Page</span>
            </a>
          </li>
          <li class="nav-item {{ request()->routeIs('termsMeta') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('termsMeta') }}">
              <i class="bi bi-file-earmark-text"></i>
              <span>Terms Page</span>
            </a>
          </li>
        </ul>
      </li>

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