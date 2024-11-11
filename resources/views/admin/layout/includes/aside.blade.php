<!-- Page Aside-->
<aside class="aside bg-white">

    <div class="simplebar-wrapper">
        <div data-pixr-simplebar>
            <div class="pb-6">
                <!-- Mobile Logo-->
                <div class="d-flex bg-theme d-xl-none justify-content-between align-items-center border-bottom aside-header">
                    <a class="navbar-brand lh-1 border-0 m-0 d-flex align-items-center" href="{{ url('/') }}">
                        <div class="d-flex align-items-center">
                            <span class="logo-text">ZENOVATE</span>
                        </div>
                    </a>
                    <i
                        class="ri-close-circle-line ri-lg close-menu text-muted transition-all text-primary-hover me-4 cursor-pointer text-white"></i>
                </div>
                <!-- / Mobile Logo-->

                <ul class="list-unstyled mb-6">

                    <!-- Dashboard Menu Section-->
                    <li class="menu-section mt-2">Menu</li>
                    <li class="menu-item"><a class="d-flex align-items-center" href="{{ route('home') }}">
                            <span class="menu-icon">
                                <i class="ri-home-line"></i>
                            </span>
                            <span class="menu-link">
                                Dashboard
                            </span></a></li>

                    <li class="menu-item"><a class="d-flex align-items-center"
                            href="{{ route('dashboard.form-sessions.index') }}">
                            <span class="menu-icon">
                                <i class="ri-inbox-line"></i>

                            </span>
                            <span class="menu-link">
                                Form Sessions
                                {{-- </span></a></li>
                    <li class="menu-item"><a class="d-flex align-items-center"
                            href="{{ route('dashboard.users.index') }}">
                            <span class="menu-icon">
                                <i class="ri-user-line"></i>

                            </span>
                            <span class="menu-link">
                                Users
                            </span></a></li> --}}
                    <li class="menu-item"><a class="d-flex align-items-center"
                            href="{{ route('dashboard.payments.index') }}">
                            <span class="menu-icon">
                                <i class="ri-folder-user-line"></i>
                            </span>
                            <span class="menu-link">
                                Payments
                            </span></a></li>


                    <li class="menu-section mt-4">Mangement</li>
                    <li class="menu-item"><a class="d-flex align-items-center"
                            href="{{ route('dashboard.admins.index') }}">
                            <span class="menu-icon">
                                <i class="ri-user-line"></i>
                            </span>
                            <span class="menu-link">
                                Admins
                            </span></a></li>

                </ul>
            </div>
        </div>
    </div>

</aside> <!-- / Page Aside-->
