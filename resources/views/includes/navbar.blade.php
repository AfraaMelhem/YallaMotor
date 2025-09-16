  <!-- HEADER -->
  <header class="app-header">
    <!-- Start::main-header-container -->
    <div class="main-header-container container-fluid">
        <!-- Start::header-content-left -->
        <div class="header-content-left">
            <!-- Start::header-element -->
            <div class="header-element">
                <div class="horizontal-logo">
                    <a href="{{ url('/') }}" class="header-logo">
                        <img src="{{ asset('build/assets/images/brand-logos/desktop-logo.png') }}" alt="logo" class="desktop-logo">
                        <img src="{{ asset('build/assets/images/brand-logos/toggle-logo.png') }}" alt="logo" class="toggle-logo">
                        <img src="{{ asset('build/assets/images/brand-logos/desktop-dark.png') }}" alt="logo" class="desktop-dark">
                        <img src="{{ asset('build/assets/images/brand-logos/toggle-dark.png') }}" alt="logo" class="toggle-dark">
                        <img src="{{ asset('build/assets/images/brand-logos/desktop-white.png') }}" alt="logo" class="desktop-white">
                        <img src="{{ asset('build/assets/images/brand-logos/toggle-white.png') }}" alt="logo" class="toggle-white">
                    </a>
                </div>
            </div>
            <!-- End::header-element -->
            <!-- Start::header-element -->
            <div class="header-element">
                <!-- Start::header-link -->
                <a aria-label="Hide Sidebar" class="sidemenu-toggle header-link animated-arrow hor-toggle horizontal-navtoggle" data-bs-toggle="sidebar" href="javascript:void(0);"><span></span></a>
                <!-- End::header-link -->
            </div>
            <!-- End::header-element -->
        </div>
        <!-- End::header-content-left -->
        <!-- Start::header-content-right -->
        <div class="header-content-right">
            <!-- Start::header-element -->
            <div class="header-element country-selector">
                <!-- Start::header-link|dropdown-toggle -->
                <a href="javascript:void(0);" class="header-link dropdown-toggle" data-bs-auto-close="outside" data-bs-toggle="dropdown">
                    <img src="{{ asset('build/assets/images/flags/us_flag.jpg') }}" alt="img" class="rounded-circle header-link-icon">
                </a>
                <!-- End::header-link|dropdown-toggle -->
                <ul class="main-header-dropdown dropdown-menu dropdown-menu-end" data-popper-placement="none">
                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="javascript:void(0);">
                            <span class="avatar avatar-xs lh-1 me-2">
                                <img src="{{ asset('build/assets/images/flags/us_flag.jpg') }}" alt="img">
                            </span>
                            English
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="javascript:void(0);">
                            <span class="avatar avatar-xs lh-1 me-2">
                                <img src="{{ asset('build/assets/images/flags/uae_flag.jpg') }}" alt="img">
                            </span>
                            Arabic
                        </a>
                    </li>
                </ul>
            </div>
            <!-- End::header-element -->
            <!-- Start::header-element -->
            <div class="header-element header-theme-mode">
                <!-- Start::header-link|layout-setting -->
                <a href="javascript:void(0);" class="header-link layout-setting">
                    <span class="light-layout">
                        <!-- Start::header-link-icon -->
                    <i class="bx bx-moon header-link-icon"></i>
                        <!-- End::header-link-icon -->
                    </span>
                    <span class="dark-layout">
                        <!-- Start::header-link-icon -->
                    <i class="bx bx-sun header-link-icon"></i>
                        <!-- End::header-link-icon -->
                    </span>
                </a>
                <!-- End::header-link|layout-setting -->
            </div>
            <!-- End::header-element -->
            <!-- Start::header-element -->
            <div class="header-element notifications-dropdown">
                <!-- Start::header-link|dropdown-toggle -->
                <a href="javascript:void(0);" class="header-link dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" id="messageDropdown" aria-expanded="false">
                    <i class="bx bx-bell header-link-icon"></i>
                    <span class="badge bg-secondary rounded-pill header-icon-badge pulse pulse-secondary" id="notification-icon-badge">5</span>
                </a>
                <!-- End::header-link|dropdown-toggle -->
                <!-- Start::main-header-dropdown -->
                <div class="main-header-dropdown dropdown-menu dropdown-menu-end" data-popper-placement="none">
                    <div class="p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <p class="mb-0 fs-17 fw-semibold">Notifications</p>
                            <span class="badge bg-secondary-transparent" id="notifiation-data">5 Unread</span>
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <ul class="list-unstyled mb-0" id="header-notification-scroll">
                        <li class="dropdown-item">
                            <div class="d-flex align-items-start">
                                <div class="pe-2">
                                    <span class="avatar avatar-md bg-primary-transparent avatar-rounded"><i class="ti ti-gift fs-18"></i></span>
                                </div>
                                <div class="flex-grow-1 d-flex align-items-center justify-content-between">
                                    <div>
                                        <p class="mb-0 fw-semibold"><a href="notifications.html">Your Order Has Been Shipped</a></p>
                                        <span class="text-muted fw-normal fs-12 header-notification-text">Order No: 123456 Has Shipped To Your Delivery Address</span>
                                    </div>
                                    <div>
                                        <a href="javascript:void(0);" class="min-w-fit-content text-muted me-1 dropdown-item-close1"><i class="ti ti-x fs-16"></i></a>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li class="dropdown-item">
                            <div class="d-flex align-items-start">
                                <div class="pe-2">
                                    <span class="avatar avatar-md bg-secondary-transparent avatar-rounded"><i class="ti ti-discount-2 fs-18"></i></span>
                                </div>
                                <div class="flex-grow-1 d-flex align-items-center justify-content-between">
                                    <div>
                                        <p class="mb-0 fw-semibold"><a href="notifications.html">Discount Available</a></p>
                                        <span class="text-muted fw-normal fs-12 header-notification-text">Discount Available On Selected Products</span>
                                    </div>
                                    <div>
                                        <a href="javascript:void(0);" class="min-w-fit-content text-muted me-1 dropdown-item-close1"><i class="ti ti-x fs-16"></i></a>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li class="dropdown-item">
                            <div class="d-flex align-items-start">
                                <div class="pe-2">
                                    <span class="avatar avatar-md bg-pink-transparent avatar-rounded"><i class="ti ti-user-check fs-18"></i></span>
                                </div>
                                <div class="flex-grow-1 d-flex align-items-center justify-content-between">
                                    <div>
                                        <p class="mb-0 fw-semibold"><a href="notifications.html">Account Has Been Verified</a></p>
                                        <span class="text-muted fw-normal fs-12 header-notification-text">Your Account Has Been Verified Sucessfully</span>
                                    </div>
                                    <div>
                                        <a href="javascript:void(0);" class="min-w-fit-content text-muted me-1 dropdown-item-close1"><i class="ti ti-x fs-16"></i></a>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li class="dropdown-item">
                            <div class="d-flex align-items-start">
                                <div class="pe-2">
                                    <span class="avatar avatar-md bg-warning-transparent avatar-rounded"><i class="ti ti-circle-check fs-18"></i></span>
                                </div>
                                <div class="flex-grow-1 d-flex align-items-center justify-content-between">
                                    <div>
                                        <p class="mb-0 fw-semibold"><a href="notifications.html">Order Placed <span class="text-warning">ID: #1116773</span></a></p>
                                        <span class="text-muted fw-normal fs-12 header-notification-text">Order Placed Successfully</span>
                                    </div>
                                    <div>
                                        <a href="javascript:void(0);" class="min-w-fit-content text-muted me-1 dropdown-item-close1"><i class="ti ti-x fs-16"></i></a>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li class="dropdown-item">
                            <div class="d-flex align-items-start">
                                <div class="pe-2">
                                    <span class="avatar avatar-md bg-success-transparent avatar-rounded"><i class="ti ti-clock fs-18"></i></span>
                                </div>
                                <div class="flex-grow-1 d-flex align-items-center justify-content-between">
                                    <div>
                                        <p class="mb-0 fw-semibold"><a href="notifications.html">Order Delayed <span class="text-success">ID: 7731116</span></a></p>
                                        <span class="text-muted fw-normal fs-12 header-notification-text">Order Delayed Unfortunately</span>
                                    </div>
                                    <div>
                                        <a href="javascript:void(0);" class="min-w-fit-content text-muted me-1 dropdown-item-close1"><i class="ti ti-x fs-16"></i></a>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <div class="p-3 empty-header-item1 border-top">
                        <div class="d-grid">
                            <a href="notifications.html" class="btn btn-primary">View All</a>
                        </div>
                    </div>
                    <div class="p-5 empty-item1 d-none">
                        <div class="text-center">
                            <span class="avatar avatar-xl avatar-rounded bg-secondary-transparent">
                                <i class="ri-notification-off-line fs-2"></i>
                            </span>
                            <h6 class="fw-semibold mt-3">No New Notifications</h6>
                        </div>
                    </div>
                </div>
                <!-- End::main-header-dropdown -->
            </div>
            <!-- End::header-element -->
            <!-- Start::header-element -->
            <div class="header-element header-shortcuts-dropdown">
                <!-- Start::header-link|dropdown-toggle -->
                <a href="javascript:void(0);" class="header-link dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" id="notificationDropdown" aria-expanded="false">
                    <i class="bx bx-grid-alt header-link-icon"></i>
                </a>
                <!-- End::header-link|dropdown-toggle -->
                <!-- Start::main-header-dropdown -->
                <div class="main-header-dropdown header-shortcuts-dropdown dropdown-menu pb-0 dropdown-menu-end" aria-labelledby="notificationDropdown">
                    <div class="p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <p class="mb-0 fs-17 fw-semibold">Related Apps</p>
                        </div>
                    </div>
                    <div class="dropdown-divider mb-0"></div>
                    <div class="main-header-shortcuts p-2" id="header-shortcut-scroll">
                    <div class="row g-2">
                        <div class="col-4">
                            <a href="javascript:void(0);">
                                    <div class="text-center p-3 related-app">
                                        <span class="avatar avatar-sm avatar-rounded">
                                            <img src="build/assets/images/apps/figma.png" alt="">
                                        </span>
                                        <span class="d-block fs-12">Figma</span>
                                    </div>
                                </a>
                        </div>
                        <div class="col-4">
                                <a href="javascript:void(0);">
                                    <div class="text-center p-3 related-app">
                                        <span class="avatar avatar-sm avatar-rounded">
                                            <img src="build/assets/images/apps/microsoft-powerpoint.png" alt="">
                                        </span>
                                        <span class="d-block fs-12">Power Point</span>
                                    </div>
                                </a>
                        </div>
                        <div class="col-4">
                                <a href="javascript:void(0);">
                                    <div class="text-center p-3 related-app">
                                        <span class="avatar avatar-sm avatar-rounded">
                                            <img src="build/assets/images/apps/microsoft-word.png" alt="">
                                        </span>
                                        <span class="d-block fs-12">MS Word</span>
                                    </div>
                                </a>
                        </div>
                        <div class="col-4">
                                <a href="javascript:void(0);">
                                    <div class="text-center p-3 related-app">
                                        <span class="avatar avatar-sm avatar-rounded">
                                            <img src="build/assets/images/apps/calender.png" alt="">
                                        </span>
                                        <span class="d-block fs-12">Calendar</span>
                                    </div>
                                </a>
                        </div>
                        <div class="col-4">
                                <a href="javascript:void(0);">
                                    <div class="text-center p-3 related-app">
                                        <span class="avatar avatar-sm avatar-rounded">
                                            <img src="build/assets/images/apps/sketch.png" alt="">
                                        </span>
                                        <span class="d-block fs-12">Sketch</span>
                                    </div>
                                </a>
                        </div>
                        <div class="col-4">
                                <a href="javascript:void(0);">
                                    <div class="text-center p-3 related-app">
                                        <span class="avatar avatar-sm avatar-rounded">
                                            <img src="build/assets/images/apps/google-docs.png" alt="">
                                        </span>
                                        <span class="d-block fs-12">Docs</span>
                                    </div>
                                </a>
                        </div>
                        <div class="col-4">
                                <a href="javascript:void(0);">
                                    <div class="text-center p-3 related-app">
                                        <span class="avatar avatar-sm avatar-rounded">
                                            <img src="build/assets/images/apps/google.png" alt="">
                                        </span>
                                        <span class="d-block fs-12">Google</span>
                                    </div>
                                </a>
                        </div>
                        <div class="col-4">
                                <a href="javascript:void(0);">
                                    <div class="text-center p-3 related-app">
                                        <span class="avatar avatar-sm avatar-rounded">
                                            <img src="build/assets/images/apps/translate.png" alt="">
                                        </span>
                                        <span class="d-block fs-12">Translate</span>
                                    </div>
                                </a>
                        </div>
                        <div class="col-4">
                                <a href="javascript:void(0);">
                                    <div class="text-center p-3 related-app">
                                        <span class="avatar avatar-sm avatar-rounded">
                                            <img src="build/assets/images/apps/google-sheets.png" alt="">
                                        </span>
                                        <span class="d-block fs-12">Sheets</span>
                                    </div>
                                </a>
                        </div>
                    </div>
                    </div>
                    <div class="p-3 border-top">
                        <div class="d-grid">
                            <a href="javascript:void(0);" class="btn btn-primary">View All</a>
                        </div>
                    </div>
                </div>
                <!-- End::main-header-dropdown -->
            </div>
            <!-- End::header-element -->
            <!-- Start::header-element -->
            <div class="header-element">
                <!-- Start::header-link|dropdown-toggle -->
                <a href="javascript:void(0);" class="header-link dropdown-toggle" id="mainHeaderProfile" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                    <div class="d-flex align-items-center">
                        <div class="me-sm-2 me-0 mx-2">
                            <img src="{{ asset('build/assets/images/faces/9.jpg') }}" alt="img" width="32" height="32" class="rounded-circle">
                        </div>
                        <div class="d-sm-block d-none">
                            <p class="fw-semibold mb-0 lh-1">Json Taylor</p>
                            <span class="op-7 fw-normal d-block fs-11">Web Designer</span>
                        </div>
                    </div>
                </a>
                <!-- End::header-link|dropdown-toggle -->
                <ul class="main-header-dropdown dropdown-menu pt-0 overflow-hidden header-profile-dropdown dropdown-menu-end" aria-labelledby="mainHeaderProfile">
                    <li><a class="dropdown-item d-flex mx-2" href="profile.html"><i class="ti ti-user-circle fs-18 me-2 op-7"></i>Profile</a></li>
                    <li><a class="dropdown-item d-flex mx-2" href="mail.html"><i class="ti ti-inbox fs-18 me-2 op-7"></i>Inbox <span class="badge bg-success-transparent ms-auto">25</span></a></li>
                    <li><a class="dropdown-item d-flex mx-2 border-block-end" href="todo-list.html"><i class="ti ti-clipboard-check fs-18 me-2 op-7"></i>Task Manager</a></li>
                    <li><a class="dropdown-item d-flex mx-2" href="mail-settings.html"><i class="ti ti-adjustments-horizontal fs-18 me-2 op-7"></i>Settings</a></li>
                    <li><a class="dropdown-item d-flex mx-2 border-block-end" href="javascript:void(0);"><i class="ti ti-wallet fs-18 me-2 op-7"></i>Bal: $7,12,950</a></li>
                    <li><a class="dropdown-item d-flex mx-2" href="chat.html"><i class="ti ti-headset fs-18 me-2 op-7"></i>Support</a></li>
                    <li><a class="dropdown-item d-flex mx-2" href="signin-cover.html"><i class="ti ti-logout fs-18 me-2 op-7"></i>Log Out</a></li>
                </ul>
            </div>  
            <!-- End::header-element -->
        </div>
        <!-- End::header-content-right -->
    </div>
    <!-- End::main-header-container -->
</header>
<!-- END HEADER -->
