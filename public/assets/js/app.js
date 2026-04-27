"use strict";
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

    if (toggleBtn && darkIcon && lightIcon) {
        toggleBtn.addEventListener("click", function () {
            const isDarkMode =
                document.documentElement.classList.contains("dark");
            document.documentElement.classList.toggle("dark", !isDarkMode);
            localStorage.setItem("color-theme", isDarkMode ? "light" : "dark");
            darkIcon.classList.toggle("hidden");
            lightIcon.classList.toggle("hidden");
        });
    }
}

// function highlightActiveMenu() {
//     const currentUrl = window.location.href;
//     const links = document.querySelectorAll("ul#sidebar-menu a");

//     links.forEach(function (link) {
//         if (link.href === currentUrl) {
//             link.classList.add("active-page");
//             let parent = link.parentElement;
//             parent.classList.add("active-page");

//             while (parent && parent.tagName !== "BODY") {
//                 if (parent.tagName === "LI") {
//                     parent.classList.add("show", "open");
//                 }
//                 parent = parent.parentElement;
//             }
//         }
//     });
// }

function highlightActiveMenu() {
    const currentUrl = window.location.pathname.replace(/\/$/, "") || "/";
    const sidebarMenu = document.getElementById("sidebar-menu");
    if (!sidebarMenu) return;

    const links = sidebarMenu.querySelectorAll("a");
    const activeClass = "active-page";

    // Clear all previous active states from the entire menu
    sidebarMenu.querySelectorAll("." + activeClass).forEach(el => {
        el.classList.remove(activeClass);
    });
    sidebarMenu.querySelectorAll(".show, .open").forEach(el => {
        el.classList.remove("show", "open");
    });

    let activeLink = null;

    links.forEach(link => {
        const linkPath = link.pathname.replace(/\/$/, "") || "/";
        if (link.getAttribute('href') === "#") return;

        const isExactMatch = currentUrl === linkPath;
        const isPrefixMatch = linkPath !== '/' && currentUrl.startsWith(linkPath + "/");

        if (isExactMatch || isPrefixMatch) {
            link.classList.add(activeClass);
            activeLink = link;

            let parentLi = link.closest("li");
            if (parentLi) {
                parentLi.classList.add(activeClass);
                
                // Open parent menus if nested
                let ancestor = parentLi.parentElement;
                while (ancestor && ancestor !== sidebarMenu) {
                    if (ancestor.tagName === "LI") {
                        ancestor.classList.add("show", "open");
                    }
                    ancestor = ancestor.parentElement;
                }
            }
        }
    });

    if (activeLink) {
        // scrollIntoView might be jarring if persisted, so we use block: 'nearest'
        activeLink.scrollIntoView({ block: "nearest" });
    }
}

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

function initSidebarToggle() {
    const toggle = document.querySelector(".sidebar-toggle");
    if (toggle && !toggle.hasAttribute("data-listener")) {
        toggle.addEventListener("click", function () {
            this.classList.toggle("active");
            document.querySelector(".sidebar").classList.toggle("active");
            document
                .querySelector(".dashboard-main")
                .classList.toggle("active");
        });
        toggle.setAttribute("data-listener", "true");
    }
}

function initSidebarDropdown() {
    const sidebarMenu = document.querySelector(".sidebar-menu");
    if (sidebarMenu && !sidebarMenu.hasAttribute("data-listener")) {
        sidebarMenu.addEventListener("click", function (e) {
            const dropdown = e.target.closest(".dropdown");
            if (!dropdown) return;

            dropdown.parentNode
                .querySelectorAll(".dropdown")
                .forEach(function (sibling) {
                    if (sibling !== dropdown) {
                        sibling.querySelector(
                            ".sidebar-submenu"
                        ).style.display = "none";
                        sibling.classList.remove("dropdown-open", "open");
                    }
                });

            const submenu = dropdown.querySelector(".sidebar-submenu");
            submenu.style.display =
                submenu.style.display === "block" ? "none" : "block";
            dropdown.classList.toggle("dropdown-open");
        });
        sidebarMenu.setAttribute("data-listener", "true");
    }
}
function initSidebarScroll() {
    const sidebarMenuArea = document.querySelector(".sidebar-menu-area");
    if (!sidebarMenuArea) return;

    // Restore scroll position
    const scrollPos = localStorage.getItem("sidebar-scroll");
    if (scrollPos) {
        sidebarMenuArea.scrollTop = scrollPos;
    }

    // Save scroll position on scroll
    if (!sidebarMenuArea.hasAttribute("data-scroll-listener")) {
        sidebarMenuArea.addEventListener("scroll", function () {
            localStorage.setItem("sidebar-scroll", sidebarMenuArea.scrollTop);
        });
        sidebarMenuArea.setAttribute("data-scroll-listener", "true");
    }
}

document.addEventListener("livewire:navigated", function () {
    // console.log("appjs");

    initSidebarDropdown();
    initSidebarToggle();
    initMobileSidebar();
    highlightActiveMenu();
    initThemeToggle();
    initSidebarScroll();
});
