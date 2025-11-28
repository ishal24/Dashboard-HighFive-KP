<t!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', 'RLEGS Dashboard')</title>

    <!-- CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.lineicons.com/5.0/lineicons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/css/bootstrap-select.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" referrerpolicy="no-referrer">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/sidebarpage.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/inertia.css') }}">

    @yield('styles')
    <style>[x-cloak]{display:none !important}</style>

    <!-- Core JavaScript -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script defer src="https://unpkg.com/alpinejs@3.13.10/dist/cdn.min.js"></script>
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
                    <a href="#">RLEGS</a>
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
                    <a href="{{ route('high-five') }}" class="sidebar-link">
                        <i class="lni lni-hierarchy-1"></i><span>High-Five</span>
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

                    <!-- Center: quick links with consistent spacing -->
                    <nav class="flex-fill">
                      <ul class="navbar-nav flex-row gap-3 mb-0 d-none d-md-flex">
                        <li class="nav-item">
                          <a href="#trend-revenue" class="nav-link d-flex align-items-center px-3 py-2">
                            <i class="lni lni-bar-chart-4 me-2"></i><span>Revenue Trend</span>
                          </a>
                        </li>
                        <li class="nav-item">
                          <a href="#witel-performance" class="nav-link d-flex align-items-center px-3 py-2">
                            <i class="lni lni-buildings-1 me-2"></i><span>Witel Achievement</span>
                          </a>
                        </li>
                        <li class="nav-item">
                          <a href="#top-customers" class="nav-link d-flex align-items-center px-3 py-2">
                            <i class="lni lni-trophy-1 me-2"></i><span>Customers Leaderboard</span>
                          </a>
                        </li>
                        {{--
                        <li class="nav-item">
                          <a href="#division-overview" class="nav-link d-flex align-items-center px-3 py-2">
                            <i class="lni lni-pie-chart-2 me-2"></i><span>Segmentation Overview</span>
                          </a>
                        </li>
                        --}}
                      </ul>
                    </nav>

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
        // Mobile responsive enhancements
        document.addEventListener('DOMContentLoaded', function() {
            // Create mobile navbar structure
            createMobileNavbar();

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
                if (window.innerWidth <= 768) {
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
                if (window.innerWidth > 768) {
                    hideMobileElements();
                } else {
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
            if (window.innerWidth <= 768) {
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

                // Clear existing content
                containerFluid.innerHTML = '';

                // Create left side (hamburger + brand)
                const navbarLeft = document.createElement('div');
                navbarLeft.className = 'navbar-left';

                // Hamburger button
                const hamburgerBtn = document.createElement('button');
                hamburgerBtn.className = 'mobile-menu-btn';
                hamburgerBtn.innerHTML = '<i class="fas fa-bars"></i>';
                hamburgerBtn.onclick = toggleSidebar;

                // Brand
                const brand = document.createElement('a');
                brand.className = 'navbar-brand';
                brand.href = '#';
                brand.textContent = 'RLEGS Dashboard';

                navbarLeft.appendChild(hamburgerBtn);
                navbarLeft.appendChild(brand);

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
                        <li><a class="dropdown-item" href="{{ route('logout') }}"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                `;

                navbarRight.appendChild(profileDropdown);

                // Append to container
                containerFluid.appendChild(navbarLeft);
                containerFluid.appendChild(navbarRight);

                // Create overlay for sidebar
                createSidebarOverlay();
            }
        }

        // Create sidebar overlay
        function createSidebarOverlay() {
            let overlay = document.querySelector('.sidebar-overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.className = 'sidebar-overlay';
                overlay.onclick = closeSidebar;
                document.body.appendChild(overlay);
            }
        }

        // Toggle sidebar function
        function toggleSidebar() {
            const sidebar = document.querySelector('#sidebar');
            const overlay = document.querySelector('.sidebar-overlay');

            if (sidebar && overlay) {
                const isOpen = sidebar.classList.contains('show');

                if (isOpen) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            }
        }

        // Open sidebar
        function openSidebar() {
            const sidebar = document.querySelector('#sidebar');
            const overlay = document.querySelector('.sidebar-overlay');

            if (sidebar && overlay) {
                sidebar.classList.add('show');
                overlay.classList.add('show');
                document.body.style.overflow = 'hidden';

                // Re-initialize dropdown functionality when sidebar opens
                initializeSidebarDropdowns();

                // Add click handlers to sidebar links for mobile
                const sidebarLinks = sidebar.querySelectorAll('.sidebar-link:not(.has-dropdown)');
                sidebarLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        if (window.innerWidth <= 768) {
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
            const sidebar = document.querySelector('#sidebar');
            const overlay = document.querySelector('.sidebar-overlay');

            if (sidebar && overlay) {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
                document.body.style.overflow = '';
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
            if (window.innerWidth <= 768) {
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
            if (window.innerWidth <= 768) {
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
            if (viewport && window.innerWidth <= 768) {
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
                if (dropdown && window.innerWidth <= 768) {
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
                if (window.innerWidth <= 768) {
                    createMobileNavbar();
                    initializeSidebarDropdowns();
                } else {
                    hideMobileElements();
                }
            }, 150);
        });

        // Focus management for sidebar
        function manageFocus() {
            const sidebar = document.querySelector('#sidebar');
            if (!sidebar) return;

            const firstFocusableElement = sidebar.querySelector('a, button');
            const lastFocusableElement = sidebar.querySelector('.sidebar-footer .sidebar-link:last-child');

            if (sidebar.classList.contains('show')) {
                if (firstFocusableElement) {
                    firstFocusableElement.focus();
                }

                // Trap focus within sidebar
                sidebar.addEventListener('keydown', function(e) {
                    if (e.key === 'Tab') {
                        if (e.shiftKey) {
                            if (document.activeElement === firstFocusableElement) {
                                if (lastFocusableElement) {
                                    lastFocusableElement.focus();
                                    e.preventDefault();
                                }
                            }
                        } else {
                            if (document.activeElement === lastFocusableElement) {
                                if (firstFocusableElement) {
                                    firstFocusableElement.focus();
                                    e.preventDefault();
                                }
                            }
                        }
                    }
                });
            }
        }

        // Smooth animations for sidebar transitions
        function addSmoothTransitions() {
            const sidebar = document.querySelector('#sidebar');
            if (sidebar && window.innerWidth <= 768) {
                sidebar.style.transition = 'left 0.3s cubic-bezier(0.4, 0, 0.2, 1)';

                // Add bounce effect when opening
                sidebar.addEventListener('transitionend', function() {
                    if (sidebar.classList.contains('show')) {
                        sidebar.style.transform = 'translateX(2px)';
                        setTimeout(() => {
                            sidebar.style.transform = 'translateX(0)';
                        }, 100);
                    }
                });
            }
        }

        // Initialize smooth transitions
        addSmoothTransitions();

        // Optimize for mobile performance
        function optimizeForMobile() {
            if (window.innerWidth <= 768) {
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
                createMobileNavbar();
                addSmoothTransitions();
                manageFocus();
            });
        } else {
            initializeSidebarDropdowns();
            createMobileNavbar();
            addSmoothTransitions();
            manageFocus();
        }

        // Debug function for mobile testing
        function debugMobile() {
            console.log('Screen width:', window.innerWidth);
            console.log('Sidebar visible:', document.querySelector('#sidebar')?.classList.contains('show'));
            console.log('Mobile navbar created:', !!document.querySelector('.mobile-menu-btn'));
            console.log('Dropdowns initialized:', document.querySelectorAll('#sidebar .sidebar-link.has-dropdown').length);
        }

        // Expose debug function globally for testing
        window.debugMobile = debugMobile;

        // Clean up function
        function cleanup() {
            // Remove event listeners when not needed
            window.removeEventListener('resize', preventHorizontalScroll);
            document.removeEventListener('touchstart', function() {});
            document.removeEventListener('touchend', function() {});
        }

        // Service worker registration for offline functionality (optional)
        if ('serviceWorker' in navigator && window.innerWidth <= 768) {
            navigator.serviceWorker.register('/sw.js').catch(function(error) {
                console.log('ServiceWorker registration failed:', error);
            });
        }
    </script>

    @yield('scripts')
    @stack('scripts')
</body>
</html>
