<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', 'RLEGS Dashboard')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.lineicons.com/5.0/lineicons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/css/bootstrap-select.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" referrerpolicy="no-referrer">

    <!-- 1) Font + Global Typography -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">

    <!-- 2) Komponen/layout spesifik -->
    <link rel="stylesheet" href="{{ asset('css/sidebarpage.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">

    @yield('styles')
    <style>[x-cloak]{display:none !important}</style>

    <!-- Core JavaScript -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Mobile Responsive Styles -->
    <style>
        /* Avatar styling */
        .avatar-container {
            width: 35px;
            height: 35px;
            overflow: hidden;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .avatar-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }

        /* ========== SIDEBAR MOBILE RESPONSIVE ========== */
        @media (max-width: 1024px) {
            /* CRITICAL: Override external CSS - Force hide sidebar by default */
            body aside#sidebar,
            body #sidebar,
            aside#sidebar,
            #sidebar {
                position: fixed !important;
                top: 0 !important;
                left: -280px !important;
                width: 280px !important;
                height: 100vh !important;
                z-index: 1040 !important;
                transition: left 0.3s ease-in-out !important;
                box-shadow: 2px 0 15px rgba(0,0,0,0.1) !important;
                overflow-y: auto !important;
                overflow-x: hidden !important;
                display: block !important;
                visibility: visible !important;
                transform: translateX(0) !important;
                margin-left: 0 !important;
                background: white !important;
                border-right: 1px solid #e9ecef !important;
            }

            /* CRITICAL: Show sidebar when active - ULTRA HIGH specificity */
            body aside#sidebar.show,
            body #sidebar.show,
            aside#sidebar.show,
            #sidebar.show {
                left: 0 !important;
                transform: translateX(0) !important;
                margin-left: 0 !important;
            }

            /* OVERRIDE: Disable external sidebar toggle behavior */
            body.toggle-sidebar aside#sidebar,
            body.toggle-sidebar #sidebar {
                left: -280px !important;
            }

            body.toggle-sidebar aside#sidebar.show,
            body.toggle-sidebar #sidebar.show {
                left: 0 !important;
            }

            /* Sidebar header styling */
            #sidebar .d-flex,
            aside#sidebar .d-flex {
                padding: 15px !important;
                display: flex !important;
                align-items: center !important;
                background: transparent !important;
            }

            #sidebar .sidebar-logo,
            aside#sidebar .sidebar-logo {
                margin-left: 10px !important;
                display: block !important;
                opacity: 1 !important;
            }

            #sidebar .sidebar-logo a,
            aside#sidebar .sidebar-logo a {
                font-weight: 600 !important;
                font-size: 18px !important;
                text-decoration: none !important;
                display: block !important;
            }

            /* CRITICAL: Force logo button to not toggle external sidebar */
            #sidebar #toggle-btn,
            aside#sidebar #toggle-btn {
                pointer-events: none !important;
                cursor: default !important;
            }

            /* Sidebar navigation - FORCE SHOW TEXT */
            #sidebar .sidebar-nav,
            aside#sidebar .sidebar-nav {
                list-style: none !important;
                padding: 0 !important;
                margin: 0 !important;
                overflow: visible !important;
            }

            #sidebar .sidebar-item,
            aside#sidebar .sidebar-item {
                margin: 0 !important;
                padding: 0 !important;
                overflow: visible !important;
            }

            #sidebar .sidebar-link,
            aside#sidebar .sidebar-link {
                display: flex !important;
                align-items: center !important;
                padding: 15px 20px !important;
                text-decoration: none !important;
                transition: all 0.2s ease !important;
                text-align: left !important;
                justify-content: flex-start !important;
                position: relative !important;
                width: 100% !important;
                border-radius: 8px !important;
                margin: 4px 8px !important;
                width: calc(100% - 16px) !important;
            }

            /* Hover effect sama kayak desktop */
            #sidebar .sidebar-link:hover,
            aside#sidebar .sidebar-link:hover {
                background: rgba(220, 53, 69, 0.1) !important;
                color: #dc3545 !important;
                transform: translateX(5px) !important;
            }

            /* Active state */
            #sidebar .sidebar-link.active,
            aside#sidebar .sidebar-link.active {
                background: rgba(220, 53, 69, 0.15) !important;
                color: #dc3545 !important;
                font-weight: 600 !important;
            }

            #sidebar .sidebar-link i,
            aside#sidebar .sidebar-link i {
                margin-right: 12px !important;
                width: 20px !important;
                text-align: center !important;
                font-size: 18px !important;
                flex-shrink: 0 !important;
                display: inline-block !important;
            }

            /* ULTRA CRITICAL: FORCE SHOW TEXT - Override external CSS */
            #sidebar .sidebar-link span,
            aside#sidebar .sidebar-link span,
            body #sidebar .sidebar-link span,
            body aside#sidebar .sidebar-link span {
                display: inline-block !important;
                text-align: left !important;
                flex-grow: 1 !important;
                font-weight: 400 !important;
                font-size: 15px !important;
                white-space: nowrap !important;
                opacity: 1 !important;
                visibility: visible !important;
                width: auto !important;
                min-width: 150px !important;
                max-width: none !important;
                overflow: visible !important;
                transition: none !important;
                padding-left: 0 !important;
                margin-left: 0 !important;
            }

            /* Force parent container to show overflow */
            #sidebar .sidebar-link,
            aside#sidebar .sidebar-link,
            body #sidebar .sidebar-link,
            body aside#sidebar .sidebar-link {
                overflow: visible !important;
            }

            #sidebar .sidebar-nav,
            aside#sidebar .sidebar-nav,
            body #sidebar .sidebar-nav,
            body aside#sidebar .sidebar-nav {
                overflow: visible !important;
            }

            #sidebar .sidebar-item,
            aside#sidebar .sidebar-item,
            body #sidebar .sidebar-item,
            body aside#sidebar .sidebar-item {
                overflow: visible !important;
            }

            /* Footer styling */
            #sidebar .sidebar-footer,
            aside#sidebar .sidebar-footer {
                margin-top: auto !important;
            }

            #sidebar .sidebar-footer .sidebar-link,
            aside#sidebar .sidebar-footer .sidebar-link {
                padding: 15px 20px !important;
            }

            #sidebar .sidebar-footer .sidebar-link span,
            aside#sidebar .sidebar-footer .sidebar-link span {
                display: inline-block !important;
                opacity: 1 !important;
                visibility: visible !important;
            }

            /* Disable hover expand on mobile */
            #sidebar:hover,
            aside#sidebar:hover {
                width: 280px !important;
            }

            /* Overlay for when sidebar is open */
            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background: rgba(0,0,0,0.5);
                z-index: 1035;
                display: none;
                backdrop-filter: blur(2px);
            }

            .sidebar-overlay.show {
                display: block;
            }

            /* Force wrapper to not accommodate sidebar */
            .wrapper {
                display: flex !important;
                padding-left: 0 !important;
                margin-left: 0 !important;
                width: 100vw !important;
                max-width: 100vw !important;
                overflow-x: hidden !important;
            }

            /* Force main content to take full viewport width */
            .main {
                margin-left: 0 !important;
                padding-left: 0 !important;
                padding-right: 0 !important;
                width: 100vw !important;
                max-width: 100vw !important;
                min-width: 100vw !important;
                flex: 1 !important;
                position: relative !important;
                padding-top: 10px !important;
            }

            /* Fixed navbar with hamburger */
            .navbar {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                width: 100vw !important;
                max-width: 100vw !important;
                z-index: 1030 !important;
                height: 60px !important;
                padding: 0 10px !important;
                margin: 0 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                background: white !important;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
                border-bottom: 1px solid #e9ecef !important;
            }

            /* Hamburger menu button */
            .mobile-menu-btn {
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                width: 40px !important;
                height: 40px !important;
                border: none !important;
                background: #dc3545 !important;
                color: white !important;
                border-radius: 8px !important;
                font-size: 16px !important;
                cursor: pointer !important;
                transition: all 0.2s ease !important;
            }

            .mobile-menu-btn:hover {
                background: #bb2d3b !important;
                transform: scale(1.05) !important;
            }

            .mobile-menu-btn:active {
                background: #a02834 !important;
            }

            /* Navbar content */
            .navbar .container-fluid {
                padding: 0 !important;
                width: 100% !important;
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
            }

            /* Left side with hamburger */
            .navbar-left {
                display: flex !important;
                align-items: center !important;
                gap: 12px !important;
            }

            /* Right side with profile */
            .navbar-right {
                display: flex !important;
                align-items: center !important;
            }

            /* Profile dropdown mobile */
            .nav-item.dropdown .nav-link {
                padding: 6px 10px !important;
                border-radius: 20px !important;
                transition: background 0.2s ease !important;
                color: #2d3748 !important;
                text-decoration: none !important;
            }

            .nav-item.dropdown .nav-link:hover {
                background: #f8f9fa !important;
            }

            /* Avatar container mobile */
            .avatar-container {
                width: 32px !important;
                height: 32px !important;
                margin-right: 6px !important;
            }

            /* Profile name */
            .nav-link span {
                font-size: 14px !important;
                font-weight: 500 !important;
                color: #2d3748 !important;
            }

            /* Dropdown menu mobile */
            .dropdown-menu {
                right: 0 !important;
                left: auto !important;
                margin-top: 8px !important;
                border: none !important;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
                border-radius: 8px !important;
                min-width: 180px !important;
            }

            /* Brand/Logo mobile - simpler */
            .navbar-brand {
                font-size: 0 !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .navbar-brand img {
                display: inline-block !important;
            }

            /* Body and HTML adjustments */
            body {
                overflow-x: hidden !important;
                margin: 0 !important;
                padding: 0 !important;
                padding-top: 60px !important;
            }

            html {
                overflow-x: hidden !important;
            }

            /* Hide original navbar elements that might interfere */
            .navbar-toggler {
                display: none !important;
            }

            .navbar-collapse {
                display: none !important;
            }
        }

        @media (max-width: 576px) {
            .navbar-brand {
                font-size: 1.0rem !important;
            }

            #sidebar {
                width: 260px !important;
            }

            .avatar-container {
                width: 30px !important;
                height: 30px !important;
            }

            .nav-link span {
                font-size: 13px !important;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <aside id="sidebar">
            <div class="d-flex">
                <button id="toggle-btn" type="button">
                    <img src="{{ asset('img/twiyh.png') }}" class="avatar rounded-circle" alt="Logo" width="35" height="35" style="margin-left: 1px">
                </button>
                <div class="sidebar-logo">
                    <a href="#">RLEGS TR3</a>
                </div>
            </div>
            <ul class="sidebar-nav">
                <li class="sidebar-item">
                    <a href="{{ route('dashboard') }}" class="sidebar-link">
                        <i class="lni lni-dashboard-square-1"></i><span>Overview Data</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="{{ route('revenue.index') }}" class="sidebar-link">
                        <i class="lni lni-file-pencil"></i><span>Data Revenue</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="{{ route('witel-cc-index') }}" class="sidebar-link">
                        <i class="lni lni-buildings-1"></i><span>CC & Witel</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="{{ route('leaderboard') }}" class="sidebar-link">
                        <i class="lni lni-hierarchy-1"></i><span>Leaderboard AM</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="{{ route('high-five.index') }}" class="sidebar-link">
                        <i class="lni lni-hand-open"></i><span>High-Five</span>
                    </a>
                </li>

            </ul>
            <div class="sidebar-footer">
                <a href="{{ route('profile.index') }}" class="sidebar-link">
                    <i class="lni lni-gear-1"></i><span>Settings</span>
                </a>
            </div>
            <div class="sidebar-footer">
                <a href="{{ route('logout') }}" class="sidebar-link">
                    <i class="lni lni-exit"></i><span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="main p-0">
            <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
                <div class="container-fluid">
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                        <ul class="navbar-nav align-items-center">
                            <li class="nav-item dropdown ms-1">
                                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <div class="avatar-container me-2">
                                        <!-- FIX: check auth before this? cause using `user()?` is maybe not be the best practice? idk -->
                                        @if(Auth::user()->profile_image)
                                            <img src="{{ asset('storage/' . Auth::user()->profile_image) }}" alt="{{ Auth::user()->name }}">
                                        @else
                                            <img src="{{ asset('img/profile.png') }}" alt="Default Profile">
                                        @endif
                                    </div>
                                    <span>{{ Auth::user()->name }}</span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('profile.index') }}">
                                            {{ __('Settings') }}
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ route('logout') }}" class="m-0">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-danger">
                                                {{ __('Log Out') }}
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
            @yield('content')
        </div>
    </div>

    <!-- JavaScript -->
    <script src="{{ asset('sidebar/script.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/js/bootstrap-select.min.js"></script>

    <!-- Mobile Responsive JavaScript -->
    <script>
        // Global flag to prevent multiple navbar creation
        let mobileNavbarCreated = false;
        let isTogglingInProgress = false;

        // Set initial sidebar position for mobile
        function initializeSidebarPosition() {
            if (window.innerWidth <= 1024) {
                const sidebar = document.querySelector('#sidebar');
                if (sidebar) {
                    // Remove any external classes that might interfere
                    document.body.classList.remove('toggle-sidebar');
                    sidebar.classList.remove('expanded', 'collapsed');

                    // Force initial position
                    sidebar.style.left = '-280px';
                    sidebar.style.position = 'fixed';
                    sidebar.style.zIndex = '1040';
                    sidebar.style.width = '280px';
                    sidebar.style.transition = 'left 0.3s ease-in-out';

                    // Disable external toggle button on mobile
                    const toggleBtn = sidebar.querySelector('#toggle-btn');
                    if (toggleBtn) {
                        toggleBtn.style.pointerEvents = 'none';
                        toggleBtn.style.cursor = 'default';

                        // Remove any external event listeners
                        toggleBtn.onclick = null;
                        const newToggleBtn = toggleBtn.cloneNode(true);
                        toggleBtn.parentNode.replaceChild(newToggleBtn, toggleBtn);
                    }

                    console.log('Initial sidebar position set'); // Debug
                }
            }
        }

        // Mobile responsive enhancements
        document.addEventListener('DOMContentLoaded', function() {
            // Set initial position first
            initializeSidebarPosition();

            // Create mobile navbar structure ONCE
            if (!mobileNavbarCreated) {
                createMobileNavbar();
            }

            // Initialize sidebar dropdowns
            initializeSidebarDropdowns();

            // Handle screen orientation changes
            window.addEventListener('orientationchange', function() {
                setTimeout(function() {
                    window.dispatchEvent(new Event('resize'));
                }, 100);
            });

            // Prevent horizontal scroll on mobile
            function preventHorizontalScroll() {
                if (window.innerWidth <= 1024) {
                    document.body.style.overflowX = 'hidden';
                    const wrapper = document.querySelector('.wrapper');
                    if (wrapper) {
                        wrapper.style.overflowX = 'hidden';
                    }
                }
            }

            // Mobile touch improvements
            if ('ontouchstart' in window) {
                document.body.classList.add('touch-device');
            }

            // Run on load and resize
            preventHorizontalScroll();
            window.addEventListener('resize', function() {
                preventHorizontalScroll();
                if (window.innerWidth > 1024) {
                    hideMobileElements();
                    mobileNavbarCreated = false; // Reset flag
                } else if (!mobileNavbarCreated) {
                    // Only create if not already created
                    createMobileNavbar();
                    initializeSidebarDropdowns();
                }
            });
        });

        // Initialize sidebar dropdowns functionality
        function initializeSidebarDropdowns() {
            const dropdownLinks = document.querySelectorAll('#sidebar .sidebar-link.has-dropdown');

            dropdownLinks.forEach(function(link) {
                // Remove existing event listeners to avoid duplicates
                link.removeEventListener('click', handleDropdownClick);

                // Add new event listener
                link.addEventListener('click', handleDropdownClick);
            });
        }

        // Handle dropdown click
        function handleDropdownClick(e) {
            e.preventDefault();

            const link = e.currentTarget;
            const targetId = link.getAttribute('data-bs-target');
            const target = document.querySelector(targetId);

            if (target) {
                const isExpanded = link.getAttribute('aria-expanded') === 'true';

                // Close all other dropdowns in sidebar
                const allDropdowns = document.querySelectorAll('#sidebar .sidebar-dropdown');
                const allDropdownLinks = document.querySelectorAll('#sidebar .sidebar-link.has-dropdown');

                allDropdowns.forEach(function(dropdown) {
                    if (dropdown !== target) {
                        dropdown.classList.remove('show');
                        dropdown.style.display = 'none';
                    }
                });

                allDropdownLinks.forEach(function(dropdownLink) {
                    if (dropdownLink !== link) {
                        dropdownLink.setAttribute('aria-expanded', 'false');
                        dropdownLink.classList.add('collapsed');
                    }
                });

                // Toggle current dropdown
                if (isExpanded) {
                    // Close
                    target.classList.remove('show');
                    target.style.display = 'none';
                    link.setAttribute('aria-expanded', 'false');
                    link.classList.add('collapsed');
                } else {
                    // Open
                    target.classList.add('show');
                    target.style.display = 'block';
                    link.setAttribute('aria-expanded', 'true');
                    link.classList.remove('collapsed');
                }
            }
        }

        // Create mobile navbar structure
        function createMobileNavbar() {
            if (window.innerWidth <= 1024 && !mobileNavbarCreated) {
                console.log('Creating mobile navbar...'); // Debug

                mobileNavbarCreated = true; // Set flag

                let navbar = document.querySelector('.navbar');

                if (!navbar) {
                    navbar = document.createElement('nav');
                    navbar.className = 'navbar navbar-expand-lg navbar-light bg-white';
                    document.body.insertBefore(navbar, document.body.firstChild);
                }

                const containerFluid = navbar.querySelector('.container-fluid') || document.createElement('div');
                containerFluid.className = 'container-fluid';

                if (!navbar.contains(containerFluid)) {
                    navbar.appendChild(containerFluid);
                }

                // Clear existing content only once
                containerFluid.innerHTML = '';

                // Create left side (hamburger + brand)
                const navbarLeft = document.createElement('div');
                navbarLeft.className = 'navbar-left';

                // Hamburger button
                const hamburgerBtn = document.createElement('button');
                hamburgerBtn.className = 'mobile-menu-btn';
                hamburgerBtn.innerHTML = '<i class="fas fa-bars"></i>';
                hamburgerBtn.setAttribute('type', 'button');
                hamburgerBtn.setAttribute('aria-label', 'Toggle navigation');

                // IMPORTANT: Use addEventListener instead of onclick
                hamburgerBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Hamburger clicked via addEventListener!'); // Debug
                    toggleSidebar();
                });

                // Brand - NO LOGO, keep it clean
                // Removed to avoid too many Telkom logos

                navbarLeft.appendChild(hamburgerBtn);
                // Logo removed - cleaner navbar

                // Create right side (profile)
                const navbarRight = document.createElement('div');
                navbarRight.className = 'navbar-right';

                // Profile dropdown
                const profileDropdown = document.createElement('div');
                profileDropdown.className = 'nav-item dropdown';
                profileDropdown.innerHTML = `
                    <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="avatar-container">
                            <img src="{{ asset('img/profile.png') }}" alt="Profile">
                        </div>
                        <span>{{ Auth::user()->name ?? 'Admin' }}</span>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="profileDropdown">
                        <li><a class="dropdown-item" href="{{ route('profile.index') }}"><i class="fas fa-cog me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}" class="m-0">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                `;

                navbarRight.appendChild(profileDropdown);

                // Append to container
                containerFluid.appendChild(navbarLeft);
                containerFluid.appendChild(navbarRight);

                // Create overlay for sidebar
                createSidebarOverlay();

                console.log('Mobile navbar created successfully!'); // Debug
            } else if (window.innerWidth > 1024 && mobileNavbarCreated) {
                // Reset flag when switching to desktop
                mobileNavbarCreated = false;
            }
        }

        // Create sidebar overlay
        function createSidebarOverlay() {
            let overlay = document.querySelector('.sidebar-overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.className = 'sidebar-overlay';
                overlay.addEventListener('click', function(e) {
                    console.log('Overlay clicked!'); // Debug
                    closeSidebar();
                });
                document.body.appendChild(overlay);
                console.log('Overlay created!'); // Debug
            }
        }

        // Toggle sidebar function
        function toggleSidebar() {
            // Prevent multiple rapid clicks
            if (isTogglingInProgress) {
                console.log('Toggle already in progress, ignoring...'); // Debug
                return;
            }

            isTogglingInProgress = true;

            console.log('Toggle sidebar clicked!'); // Debug
            const sidebar = document.querySelector('#sidebar');
            const overlay = document.querySelector('.sidebar-overlay');

            console.log('Sidebar:', sidebar); // Debug
            console.log('Overlay:', overlay); // Debug

            if (sidebar && overlay) {
                const isOpen = sidebar.classList.contains('show');
                console.log('Is open:', isOpen); // Debug

                if (isOpen) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            } else {
                console.error('Sidebar or overlay not found!');
            }

            // Reset flag after animation completes
            setTimeout(() => {
                isTogglingInProgress = false;
            }, 350);
        }

        // Open sidebar
        function openSidebar() {
            console.log('Opening sidebar...'); // Debug
            const sidebar = document.querySelector('#sidebar');
            const overlay = document.querySelector('.sidebar-overlay');

            if (sidebar && overlay) {
                // Add class
                sidebar.classList.add('show');
                overlay.classList.add('show');

                // NUCLEAR: Use setProperty with 'important' priority
                sidebar.style.setProperty('left', '0px', 'important');
                sidebar.style.setProperty('position', 'fixed', 'important');
                sidebar.style.setProperty('transform', 'translateX(0)', 'important');
                sidebar.style.setProperty('z-index', '1040', 'important');
                sidebar.style.setProperty('width', '280px', 'important');
                sidebar.style.setProperty('overflow', 'visible', 'important');
                sidebar.style.setProperty('overflow-x', 'visible', 'important');
                sidebar.style.setProperty('overflow-y', 'auto', 'important');

                // Force nav and items overflow
                const sidebarNav = sidebar.querySelector('.sidebar-nav');
                if (sidebarNav) {
                    sidebarNav.style.setProperty('overflow', 'visible', 'important');
                }

                const sidebarItems = sidebar.querySelectorAll('.sidebar-item');
                sidebarItems.forEach(item => {
                    item.style.setProperty('overflow', 'visible', 'important');
                    item.style.setProperty('margin', '0', 'important');
                    item.style.setProperty('padding', '0', 'important');
                });

                // NUCLEAR: Force show all text spans with setProperty
                const allSpans = sidebar.querySelectorAll('.sidebar-link span');
                allSpans.forEach(span => {
                    span.style.setProperty('display', 'inline-block', 'important');
                    span.style.setProperty('opacity', '1', 'important');
                    span.style.setProperty('visibility', 'visible', 'important');
                    span.style.setProperty('width', 'auto', 'important');
                    span.style.setProperty('min-width', '150px', 'important');
                    span.style.setProperty('max-width', 'none', 'important');
                    span.style.setProperty('overflow', 'visible', 'important');
                    span.style.setProperty('white-space', 'nowrap', 'important');
                    span.style.setProperty('transition', 'none', 'important');
                    span.style.setProperty('margin-left', '12px', 'important');
                    span.style.setProperty('padding-left', '0', 'important');
                    span.style.setProperty('font-size', '15px', 'important');
                    span.style.setProperty('font-weight', '400', 'important');
                });

                // Force all sidebar links overflow
                const allLinks = sidebar.querySelectorAll('.sidebar-link');
                allLinks.forEach(link => {
                    link.style.setProperty('overflow', 'visible', 'important');
                    link.style.setProperty('display', 'flex', 'important');
                    link.style.setProperty('align-items', 'center', 'important');
                });

                // Force show logo text
                const logoText = sidebar.querySelector('.sidebar-logo');
                if (logoText) {
                    logoText.style.setProperty('display', 'block', 'important');
                    logoText.style.setProperty('opacity', '1', 'important');
                    logoText.style.setProperty('visibility', 'visible', 'important');
                }

                document.body.style.overflow = 'hidden';

                console.log('Sidebar opened, classes:', sidebar.className);
                console.log('Text spans forced visible:', allSpans.length);

                // Debug: Log actual HTML content
                if (allSpans.length > 0) {
                    const firstSpan = allSpans[0];
                    console.log('First span HTML:', firstSpan.outerHTML);
                    console.log('First span text content:', firstSpan.textContent);
                    console.log('First span computed width:', window.getComputedStyle(firstSpan).width);
                }

                // Re-initialize dropdown functionality when sidebar opens
                initializeSidebarDropdowns();

                // Add click handlers to sidebar links for mobile
                const sidebarLinks = sidebar.querySelectorAll('.sidebar-link:not(.has-dropdown)');
                sidebarLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        if (window.innerWidth <= 1024) {
                            setTimeout(() => {
                                closeSidebar();
                            }, 200);
                        }
                    });
                });
            }
        }

        // Close sidebar
        function closeSidebar() {
            console.log('Closing sidebar...'); // Debug
            const sidebar = document.querySelector('#sidebar');
            const overlay = document.querySelector('.sidebar-overlay');

            if (sidebar && overlay) {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');

                // Reset inline styles
                sidebar.style.left = '-280px';

                document.body.style.overflow = '';

                console.log('Sidebar closed'); // Debug
            }
        }

        // Hide mobile elements on desktop
        function hideMobileElements() {
            const overlay = document.querySelector('.sidebar-overlay');
            if (overlay) {
                overlay.remove();
            }

            const sidebar = document.querySelector('#sidebar');
            if (sidebar) {
                sidebar.classList.remove('show');
            }

            document.body.style.overflow = '';
        }

        // Handle clicks outside sidebar to close it
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 1024) {
                const sidebar = document.querySelector('#sidebar');
                const hamburger = document.querySelector('.mobile-menu-btn');

                if (sidebar && sidebar.classList.contains('show')) {
                    if (!sidebar.contains(event.target) && hamburger && !hamburger.contains(event.target)) {
                        closeSidebar();
                    }
                }
            }
        });

        // Handle swipe gestures for mobile
        let touchStartX = 0;
        let touchEndX = 0;

        document.addEventListener('touchstart', function(event) {
            touchStartX = event.changedTouches[0].screenX;
        });

        document.addEventListener('touchend', function(event) {
            touchEndX = event.changedTouches[0].screenX;
            handleSwipe();
        });

        function handleSwipe() {
            if (window.innerWidth <= 1024) {
                const swipeDistance = touchEndX - touchStartX;
                const sidebar = document.querySelector('#sidebar');

                // Swipe right to open sidebar
                if (swipeDistance > 50 && touchStartX < 50) {
                    openSidebar();
                }

                // Swipe left to close sidebar
                if (swipeDistance < -50 && sidebar && sidebar.classList.contains('show')) {
                    closeSidebar();
                }
            }
        }

        // Adjust viewport for better mobile rendering
        function adjustViewportForMobile() {
            const viewport = document.querySelector('meta[name=viewport]');
            if (viewport && window.innerWidth <= 1024) {
                viewport.setAttribute('content',
                    'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'
                );
            }
        }

        // Run viewport adjustment
        adjustViewportForMobile();
        window.addEventListener('resize', adjustViewportForMobile);

        // Accessibility improvements
        document.addEventListener('keydown', function(e) {
            // Close sidebar with Escape key
            if (e.key === 'Escape') {
                const sidebar = document.querySelector('#sidebar');
                if (sidebar && sidebar.classList.contains('show')) {
                    closeSidebar();
                }
            }

            // Handle Enter/Space for dropdown links
            if (e.key === 'Enter' || e.key === ' ') {
                const target = e.target;
                if (target.classList.contains('has-dropdown')) {
                    e.preventDefault();
                    handleDropdownClick(e);
                }
            }
        });

        // Enhanced Bootstrap dropdown initialization for mobile
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Bootstrap dropdowns
            var dropdownElementList = [].slice.call(document.querySelectorAll('[data-bs-toggle="dropdown"]'));
            var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl);
            });

            // Fix for mobile dropdown menu positioning
            document.addEventListener('show.bs.dropdown', function (e) {
                const dropdown = e.target.closest('.dropdown');
                if (dropdown && window.innerWidth <= 1024) {
                    setTimeout(function() {
                        const menu = dropdown.querySelector('.dropdown-menu');
                        if (menu) {
                            const rect = dropdown.getBoundingClientRect();
                            const viewportWidth = window.innerWidth;

                            if (rect.right + menu.offsetWidth > viewportWidth) {
                                menu.classList.add('dropdown-menu-end');
                            }
                        }
                    }, 10);
                }
            });
        });

        // Performance optimization for mobile
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(function() {
                // Recalculate layout after resize
                if (window.innerWidth <= 1024 && !mobileNavbarCreated) {
                    createMobileNavbar();
                    initializeSidebarDropdowns();
                } else if (window.innerWidth > 1024) {
                    hideMobileElements();
                    mobileNavbarCreated = false;
                }
            }, 150);
        });

        // Optimize for mobile performance
        function optimizeForMobile() {
            if (window.innerWidth <= 1024) {
                // Reduce animation complexity on mobile
                document.body.classList.add('mobile-optimized');

                // Disable hover effects on touch devices
                if ('ontouchstart' in window) {
                    document.body.classList.add('touch-device');
                }

                // Optimize scrolling
                document.body.style.webkitOverflowScrolling = 'touch';
            }
        }

        optimizeForMobile();

        // Initialize everything when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                initializeSidebarDropdowns();
                if (!mobileNavbarCreated) {
                    createMobileNavbar();
                }
            });
        } else {
            initializeSidebarDropdowns();
            if (!mobileNavbarCreated) {
                createMobileNavbar();
            }
        }
        
    </script>

    @yield('scripts')
    @stack('scripts')
</body>
</html>