<!-- Page Aside-->
<aside class="aside bg-white">

    <div class="simplebar-wrapper">
        <div data-pixr-simplebar>
            <div class="pb-6">
                <!-- Mobile Logo-->
                <div class="d-flex d-xl-none justify-content-between align-items-center border-bottom aside-header">
                    <a class="navbar-brand lh-1 border-0 m-0 d-flex align-items-center" href="./index.html">
                        <div class="d-flex align-items-center">
                            <svg class="f-w-5 me-2 text-primary d-flex align-self-center lh-1"
                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 203.58 182">
                                <path
                                    d="M101.66,41.34C94.54,58.53,88.89,72.13,84,83.78A21.2,21.2,0,0,1,69.76,96.41,94.86,94.86,0,0,0,26.61,122.3L81.12,0h41.6l55.07,123.15c-12-12.59-26.38-21.88-44.25-26.81a21.22,21.22,0,0,1-14.35-12.69c-4.71-11.35-10.3-24.86-17.53-42.31Z"
                                    fill="currentColor" fill-rule="evenodd" fill-opacity="0.5" />
                                <path
                                    d="M0,182H29.76a21.3,21.3,0,0,0,18.56-10.33,63.27,63.27,0,0,1,106.94,0A21.3,21.3,0,0,0,173.82,182h29.76c-22.66-50.84-49.5-80.34-101.79-80.34S22.66,131.16,0,182Z"
                                    fill="currentColor" fill-rule="evenodd" />
                            </svg>
                            <span class="fw-black text-uppercase tracking-wide fs-6 lh-1">Apollo</span>
                        </div>
                    </a>
                    <i
                        class="ri-close-circle-line ri-lg close-menu text-muted transition-all text-primary-hover me-4 cursor-pointer"></i>
                </div>
                <!-- / Mobile Logo-->

                <ul class="list-unstyled mb-6">

                    <!-- Dashboard Menu Section-->
                    <li class="menu-section mt-2">General</li>
                    <li class="menu-item"><a class="d-flex align-items-center" href="{{ route('home') }}">
                            <span class="menu-icon">
                                <i class="ri-home-line"></i>
                            </span>
                            <span class="menu-link">
                                Dashboard
                            </span></a></li>

                    <li class="menu-section mt-2">Subscription</li>
                    <li class="menu-item"><a class="d-flex align-items-center"
                            href="{{ route('dashboard.users.index') }}">
                            <span class="menu-icon">
                                <i class="ri-user-line"></i>

                            </span>
                            <span class="menu-link">
                                Users
                            </span></a></li>
                    <li class="menu-item"><a class="d-flex align-items-center"
                            href="{{ route('dashboard.applications.index') }}">
                            <span class="menu-icon">
                                <i class="ri-folder-user-line"></i>
                            </span>
                            <span class="menu-link">
                                Applications
                            </span></a></li>
                            <li class="menu-item"><a class="d-flex align-items-center"
                                href="{{ route('dashboard.payments.index') }}">
                                <span class="menu-icon">
                                    <i class="ri-folder-user-line"></i>
                                </span>
                                <span class="menu-link">
                                    Payments
                                </span></a></li>
                    <!-- / Dashboard Menu Section-->



                    <!-- Pages Menu Section-->
                    <li class="menu-section mt-4">Consultations</li>
                    {{-- <li class="menu-item"><a class="d-flex align-items-center"
                            href="{{ route("dashboard.consultation.patient-records.create") }}">
                            <span class="menu-icon">
                                <i class="ri-folder-add-fill"></i>
                            </span>
                            <span class="menu-link">
                                New Record
                            </span></a></li>
                    <li class="menu-item"><a class="d-flex align-items-center"
                            href="{{ route("dashboard.consultation.patient-records.index") }}">
                            <span class="menu-icon">
                                <i class="ri-folder-5-fill"></i>
                            </span>
                            <span class="menu-link">
                                Records
                            </span></a></li> --}}
                    <li class="menu-item"><a class="d-flex align-items-center"
                            href="{{ route("dashboard.consultation.patients.index") }}">
                            <span class="menu-icon">
                                <i class="ri-shield-user-line"></i>
                            </span>
                            <span class="menu-link">
                                Patients
                            </span></a></li>


                            <li class="menu-section mt-4">Mangement</li>
                            <li class="menu-item"><a class="d-flex align-items-center" href="{{ route("dashboard.admins.index") }}">
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
