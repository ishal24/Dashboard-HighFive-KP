/**
 * SCRIPT.JS - FIXED VERSION
 * ✅ FIXED: Variable conflicts with revenue.js
 * ✅ FIXED: Bootstrap dependency issues
 * ✅ FIXED: Element existence checks
 * ✅ FIXED: Namespace conflicts
 */

'use strict';

// ===================================================================
// SIDEBAR MANAGEMENT - FIXED: Proper namespace and checks
// ===================================================================

document.addEventListener('DOMContentLoaded', function() {
    // 🔧 FIXED: Proper element existence check
    const sidebarToggle = document.querySelector("#toggle-btn");
    const sidebar = document.querySelector("#sidebar");

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener("click", function(){
            sidebar.classList.toggle("expand");
        });
        console.log('✅ Sidebar toggle initialized');
    } else {
        console.warn('⚠️ Sidebar elements not found - skipping sidebar functionality');
    }

    // ===================================================================
    // FILTER DROPDOWN - FIXED: Safe initialization
    // ===================================================================

    const filterDropdown = document.getElementById("filterDropdown");
    if (filterDropdown) {
        // Prevent dropdown close when clicking inside
        filterDropdown.addEventListener("click", function(event) {
            event.stopPropagation();
        });
        console.log('✅ Filter dropdown initialized');
    }

    // ===================================================================
    // TAB NAVIGATION - FIXED: Safe element checks and unique variables
    // ===================================================================

    const leftBtn = document.querySelector(".left-btn");
    const rightBtn = document.querySelector(".right-btn");
    const tabMenuElement = document.querySelector(".tab-menu"); // 🔧 RENAMED to avoid conflict

    if (leftBtn && rightBtn && tabMenuElement) {
        let tabDragActive = false; // 🔧 RENAMED to avoid conflict

        // Function to control navigation button visibility
        const updateTabNavVisibility = () => {
            let scrollLeftValue = Math.ceil(tabMenuElement.scrollLeft);
            let scrollableWidth = tabMenuElement.scrollWidth - tabMenuElement.clientWidth;

            leftBtn.style.display = scrollLeftValue > 0 ? "block" : "none";
            rightBtn.style.display = scrollableWidth > scrollLeftValue ? "block" : "none";
        };

        // Navigation button event listeners
        rightBtn.addEventListener("click", () => {
            tabMenuElement.scrollLeft += 150;
            setTimeout(updateTabNavVisibility, 100);
        });

        leftBtn.addEventListener("click", () => {
            tabMenuElement.scrollLeft -= 150;
            setTimeout(updateTabNavVisibility, 100);
        });

        // Scroll detection
        tabMenuElement.addEventListener("scroll", updateTabNavVisibility);

        // 🔧 FIXED: Drag functionality with proper namespace
        tabMenuElement.addEventListener("mousemove", (drag) => {
            if(!tabDragActive) return;
            tabMenuElement.scrollLeft -= drag.movementX;
        });

        tabMenuElement.addEventListener("mousedown", () => {
            tabDragActive = true;
        });

        document.addEventListener("mouseup", () => {
            tabDragActive = false;
        });

        // Initial visibility check
        updateTabNavVisibility();

        console.log('✅ Tab navigation initialized');
    } else {
        console.warn('⚠️ Tab navigation elements not found - skipping tab functionality');
    }

    // ===================================================================
    // BOOTSTRAP COMPONENTS - FIXED: Proper loading order check
    // ===================================================================

    // Wait for Bootstrap to be fully loaded
    if (typeof bootstrap !== 'undefined') {
        initializeBootstrapComponents();
    } else {
        // Wait a bit more for Bootstrap to load
        setTimeout(() => {
            if (typeof bootstrap !== 'undefined') {
                initializeBootstrapComponents();
            } else {
                console.warn('⚠️ Bootstrap not loaded - some components may not work');
            }
        }, 500);
    }
});

function initializeBootstrapComponents() {
    try {
        // ===================================================================
        // FILTER TABS - FIXED: Bootstrap tab handling
        // ===================================================================

        const filterTabs = document.querySelectorAll('#filterTabs .nav-link');

        if (filterTabs.length > 0) {
            filterTabs.forEach(function(tab) {
                tab.addEventListener('click', function(e) {
                    e.preventDefault();

                    // Remove active class from all tabs
                    filterTabs.forEach(function(t) {
                        t.classList.remove('active');
                    });

                    // Add active class to clicked tab
                    this.classList.add('active');

                    // Hide all tab panes
                    const tabPanes = document.querySelectorAll('.tab-pane');
                    tabPanes.forEach(function(pane) {
                        pane.classList.remove('show', 'active');
                    });

                    // Show the corresponding tab pane
                    const targetId = this.getAttribute('data-bs-target');
                    if (targetId) {
                        const targetPane = document.querySelector(targetId);
                        if (targetPane) {
                            targetPane.classList.add('show', 'active');
                        }
                    }
                });
            });
            console.log('✅ Filter tabs initialized');
        }

        // ===================================================================
        // DROPDOWN MANAGEMENT - FIXED: Proper Bootstrap dropdown
        // ===================================================================

        const filterDropdownBtn = document.querySelector('[data-bs-toggle="dropdown"]');
        const filterDropdown = document.getElementById('filterDropdown');

        if (filterDropdownBtn && filterDropdown) {
            filterDropdownBtn.addEventListener('click', function() {
                filterDropdown.classList.toggle('show');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!filterDropdownBtn.contains(e.target) && !filterDropdown.contains(e.target)) {
                    filterDropdown.classList.remove('show');
                }
            });
            console.log('✅ Filter dropdown initialized');
        }

        // ===================================================================
        // PERIODE TAB - FIXED: Safe tab creation
        // ===================================================================

        if (!document.getElementById('periode')) {
            const tabContent = document.querySelector('.tab-content');
            if (tabContent) {
                const periodeTab = document.createElement('div');
                periodeTab.id = 'periode';
                periodeTab.className = 'tab-pane fade';
                periodeTab.innerHTML = `
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="periode1" value="2023">
                        <label class="form-check-label" for="periode1">2023</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="periode2" value="2024">
                        <label class="form-check-label" for="periode2">2024</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="periode3" value="2025">
                        <label class="form-check-label" for="periode3">2025</label>
                    </div>
                `;
                tabContent.appendChild(periodeTab);
                console.log('✅ Periode tab created');
            }
        }

        console.log('✅ Bootstrap components initialized');

    } catch (error) {
        console.error('❌ Error initializing Bootstrap components:', error);
    }
}

// ===================================================================
// UTILITY FUNCTIONS - FIXED: Namespace protection
// ===================================================================

window.sidebarUtils = {
    toggleSidebar: function() {
        const sidebar = document.querySelector("#sidebar");
        if (sidebar) {
            sidebar.classList.toggle("expand");
        }
    },

    updateTabVisibility: function() {
        const leftBtn = document.querySelector(".left-btn");
        const rightBtn = document.querySelector(".right-btn");
        const tabMenuElement = document.querySelector(".tab-menu");

        if (leftBtn && rightBtn && tabMenuElement) {
            let scrollLeftValue = Math.ceil(tabMenuElement.scrollLeft);
            let scrollableWidth = tabMenuElement.scrollWidth - tabMenuElement.clientWidth;

            leftBtn.style.display = scrollLeftValue > 0 ? "block" : "none";
            rightBtn.style.display = scrollableWidth > scrollLeftValue ? "block" : "none";
        }
    }
};

console.log('✅ Sidebar script loaded without conflicts');