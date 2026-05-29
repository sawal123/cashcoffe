"use strict";

// ===================================
//  THEME TOGGLE
// ===================================
function initThemeToggle() {
    const darkIcon = document.getElementById("theme-toggle-dark-icon");
    const lightIcon = document.getElementById("theme-toggle-light-icon");
    const toggleBtn = document.getElementById("theme-toggle");

    const isDark =
        localStorage.getItem("color-theme") === "dark" ||
        (!localStorage.getItem("color-theme") &&
            window.matchMedia("(prefers-color-scheme: dark)").matches);

    document.documentElement.classList.toggle("dark", isDark);
    if (darkIcon && lightIcon) {
        darkIcon.classList.toggle("hidden", isDark);
        lightIcon.classList.toggle("hidden", !isDark);
    }

    // Guard: only attach click listener once (persisted navbar shares same DOM node)
    if (toggleBtn && !toggleBtn.hasAttribute("data-theme-listener")) {
        toggleBtn.setAttribute("data-theme-listener", "true");
        toggleBtn.addEventListener("click", function () {
            const isDarkMode = document.documentElement.classList.contains("dark");
            document.documentElement.classList.toggle("dark", !isDarkMode);
            localStorage.setItem("color-theme", isDarkMode ? "light" : "dark");
            if (darkIcon && lightIcon) {
                darkIcon.classList.toggle("hidden");
                lightIcon.classList.toggle("hidden");
            }
        });
    }
}

// ===================================
//  FIXED NAVBAR LAYOUT SYNC
// ===================================
function syncFixedNavbarLayout() {
    const navbar = document.querySelector(".navbar-header");
    const dashboardMain = document.querySelector(".dashboard-main");

    if (!navbar || !dashboardMain) return;

    const desktop = window.matchMedia("(min-width: 1200px)").matches;
    const computedMain = window.getComputedStyle(dashboardMain);
    const startOffset = desktop
        ? (computedMain.marginInlineStart || computedMain.marginLeft || "0px")
        : "0px";

    navbar.style.position = "fixed";
    navbar.style.top = "0";
    navbar.style.insetInlineStart = startOffset;
    navbar.style.insetInlineEnd = "0";
    navbar.style.zIndex = "20";

    // Reserve space for fixed navbar so page content doesn't jump under it.
    dashboardMain.style.paddingTop = "4.5rem";
}

function initFixedNavbarLayout() {
    syncFixedNavbarLayout();

    if (!window.__fixedNavbarResizeBound) {
        window.addEventListener("resize", syncFixedNavbarLayout);
        window.__fixedNavbarResizeBound = true;
    }
}

// ===================================
//  SIDEBAR ACTIVE HIGHLIGHT
// ===================================
function highlightActiveMenu() {
    const currentUrl = window.location.pathname.replace(/\/$/, "") || "/";
    const sidebarMenu = document.getElementById("sidebar-menu");
    if (!sidebarMenu) return;

    const activeClass = "active-page";

    // --- Step 1: Bersihkan SEMUA status aktif di seluruh menu ---
    sidebarMenu.querySelectorAll("." + activeClass).forEach(el => {
        el.classList.remove(activeClass);
    });
    sidebarMenu.querySelectorAll(".show, .open").forEach(el => {
        el.classList.remove("show", "open");
    });

    // --- Step 2: Tandai menu yang sesuai URL saat ini ---
    let activeLink = null;
    let maxMatchScore = -1;
    const currentParams = new URLSearchParams(window.location.search);

    sidebarMenu.querySelectorAll("a").forEach(link => {
        if (link.getAttribute("href") === "#") return;

        const linkUrl = new URL(link.href, window.location.origin);
        const linkPath = linkUrl.pathname.replace(/\/$/, "") || "/";
        const isExactMatch = currentUrl === linkPath;
        const isPrefixMatch = linkPath !== "/" && currentUrl.startsWith(linkPath + "/") && !link.hasAttribute("data-exact-match");

            if (isExactMatch || isPrefixMatch) {
                let queryMatches = true;
                let matchScore = 0;
                
                const linkParams = linkUrl.searchParams;
                for (const [key, val] of linkParams.entries()) {
                    matchScore++;
                    if (currentParams.get(key) !== val) {
                        const isDefaultLeaveCuti = (key === 'type' && val === 'leave' && !currentParams.has('type')) ||
                                                   (key === 'jenis' && val === 'cuti' && !currentParams.has('jenis'));
                        if (!isDefaultLeaveCuti) {
                            queryMatches = false;
                            break;
                        }
                    }
                }

                if (queryMatches && matchScore > maxMatchScore) {
                    maxMatchScore = matchScore;
                    activeLink = link;
                }
            }
    });

    if (activeLink) {
        activeLink.classList.add(activeClass);
        const parentLi = activeLink.closest("li");
        if (parentLi) {
            parentLi.classList.add(activeClass);

            // Buka parent menu jika bertingkat
            let ancestor = parentLi.parentElement;
            while (ancestor && ancestor !== sidebarMenu) {
                if (ancestor.tagName === "LI") {
                    ancestor.classList.add("show", "open");
                }
                ancestor = ancestor.parentElement;
            }
        }
        activeLink.scrollIntoView({ block: "nearest" });
    }
}

// ===================================
//  MOBILE SIDEBAR
// ===================================
function initMobileSidebar() {
    const openBtn = document.querySelector(".sidebar-mobile-toggle");
    const closeBtn = document.querySelector(".sidebar-close-btn");

    if (openBtn && !openBtn.hasAttribute("data-listener")) {
        openBtn.addEventListener("click", function () {
            document.querySelector(".sidebar").classList.add("sidebar-open");
            document.body.classList.add("overlay-active");
        });
        openBtn.setAttribute("data-listener", "true");
    }

    if (closeBtn && !closeBtn.hasAttribute("data-listener")) {
        closeBtn.addEventListener("click", function () {
            document.querySelector(".sidebar").classList.remove("sidebar-open");
            document.body.classList.remove("overlay-active");
        });
        closeBtn.setAttribute("data-listener", "true");
    }
}

// ===================================
//  SIDEBAR TOGGLE (COLLAPSE)
// ===================================
function initSidebarToggle() {
    const toggle = document.querySelector(".sidebar-toggle");
    if (toggle && !toggle.hasAttribute("data-listener")) {
        toggle.addEventListener("click", function () {
            this.classList.toggle("active");
            document.querySelector(".sidebar").classList.toggle("active");
            document.querySelector(".dashboard-main").classList.toggle("active");
            syncFixedNavbarLayout();
            setTimeout(syncFixedNavbarLayout, 250);
        });
        toggle.setAttribute("data-listener", "true");
    }
}

// ===================================
//  SIDEBAR DROPDOWN
// ===================================
function initSidebarDropdown() {
    const sidebarMenu = document.querySelector(".sidebar-menu");
    if (sidebarMenu && !sidebarMenu.hasAttribute("data-listener")) {
        sidebarMenu.addEventListener("click", function (e) {
            const dropdown = e.target.closest(".dropdown");
            if (!dropdown) return;

            dropdown.parentNode.querySelectorAll(".dropdown").forEach(function (sibling) {
                if (sibling !== dropdown) {
                    const sibSubmenu = sibling.querySelector(".sidebar-submenu");
                    if (sibSubmenu) sibSubmenu.style.display = "none";
                    sibling.classList.remove("dropdown-open", "open");
                }
            });

            const submenu = dropdown.querySelector(".sidebar-submenu");
            if (submenu) {
                submenu.style.display = submenu.style.display === "block" ? "none" : "block";
            }
            dropdown.classList.toggle("dropdown-open");
        });
        sidebarMenu.setAttribute("data-listener", "true");
    }
}

// ===================================
//  SIDEBAR SCROLL PERSISTENCE
// ===================================
function initSidebarScroll() {
    const sidebarMenuArea = document.querySelector(".sidebar-menu-area");
    if (!sidebarMenuArea) return;

    const scrollPos = localStorage.getItem("sidebar-scroll");
    if (scrollPos) {
        sidebarMenuArea.scrollTop = parseInt(scrollPos, 10);
    }

    if (!sidebarMenuArea.hasAttribute("data-scroll-listener")) {
        sidebarMenuArea.addEventListener("scroll", function () {
            localStorage.setItem("sidebar-scroll", sidebarMenuArea.scrollTop);
        });
        sidebarMenuArea.setAttribute("data-scroll-listener", "true");
    }
}

// ===================================
//  EVENT: SETELAH NAVIGASI
// ===================================
document.addEventListener("livewire:navigated", function () {
    initSidebarDropdown();
    initSidebarToggle();
    initMobileSidebar();
    initThemeToggle();
    initSidebarScroll();
    highlightActiveMenu();
    initFixedNavbarLayout();
});

document.addEventListener("DOMContentLoaded", function () {
    initSidebarDropdown();
    initSidebarToggle();
    initMobileSidebar();
    initThemeToggle();
    initSidebarScroll();
    highlightActiveMenu();
    initFixedNavbarLayout();
});
