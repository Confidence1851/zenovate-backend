<!-- Navbar-->
<nav class="navbar navbar-expand-lg navbar-light border-bottom py-0 fixed-top bg-white">
    <div class="container-fluid bg-theme">
        <a class="navbar-brand d-flex justify-content-start align-items-center border-end" href="{{ route('home') }}">
            <div class="text-center">
                <span class="logo-text">ZENOVATE</span>
                {{-- <img src="{{ asset("logo.png") }}" alt=""> --}}
            </div>
        </a>
        <div class="d-flex justify-content-between align-items-center flex-grow-1 navbar-actions">

            <!-- Search Bar and Menu Toggle-->
            <div class="d-flex align-items-center">

                <!-- Menu Toggle-->
                <div
                    class="menu-toggle text-white cursor-pointer me-4 text-primary-hover transition-color disable-child-pointer">
                    <i class="ri-skip-back-mini-line ri-lg fold align-middle" data-bs-toggle="tooltip"
                        data-bs-placement="right" title="Close menu"></i>
                    <i class="ri-skip-forward-mini-line ri-lg unfold align-middle" data-bs-toggle="tooltip"
                        data-bs-placement="right" title="Open Menu"></i>
                </div>
                <!-- / Menu Toggle-->

                <!-- Search Bar-->
                {{-- <form class="d-none d-md-flex bg-light rounded px-3 py-1">
              <input class="form-control border-0 bg-transparent px-0 py-2 me-5 fw-bolder" type="search"
                placeholder="Search" aria-label="Search">
              <button class="btn btn-link p-0 text-muted" type="submit"><i class="ri-search-2-line"></i></button>
          </form>        <!-- / Search Bar--> --}}

            </div>
            <!-- / Search Bar and Menu Toggle-->

            <!-- Right Side Widgets-->
            <div class="d-flex align-items-center">

                <!-- Profile Menu-->
                <div class="dropdown ms-1">
                    <button class="btn btn-link p-0 position-relative" type="button" id="profileDropdown"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <picture>
                            <img class="f-w-10 rounded-circle" src="{{ asset('user.jpg') }}" alt="">
                        </picture>
                        <span
                            class="position-absolute bottom-0 start-75 p-1 bg-success border border-3 border-white rounded-circle">
                            <span class="visually-hidden">New alerts</span>
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-md dropdown-menu-end" aria-labelledby="profileDropdown">
                        {{-- <li><a class="dropdown-item d-flex align-items-center" href="#">Edit Profile</a></li> --}}
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li class="d-flex py-2 align-items-start">
                            <button
                                class="btn-icon bg-primary-faded text-primary fw-bolder me-3">{{ auth()->user()->first_name[0] }}</button>
                            <div class="d-flex align-items-start justify-content-between flex-grow-1">
                                <div>
                                    <p class="lh-1 mb-2 fw-semibold text-body">{{ auth()->user()->full_name }}</p>
                                    <p class="text-muted lh-1 mb-2 small">{{ auth()->user()->email }}</p>
                                </div>
                            </div>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item d-flex align-items-center text-danger" href="#"
                                onclick="return document.getElementById('logoutBtn').click();">Logout</a></li>
                        <form method="POST" onsubmit="return confirm('Are you sure you want logout?')"
                            action="{{ route('logout') }}" id="logoutForm">@csrf
                            <button class="d-none" id="logoutBtn"></button>
                        </form>
                    </ul>
                </div> <!-- / Profile Menu-->

            </div>
            <!-- / Notifications & Profile-->
        </div>
    </div>
</nav> <!-- / Navbar-->
