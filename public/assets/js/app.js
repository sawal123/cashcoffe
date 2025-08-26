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
    const currentUrl = window.location.pathname; // ambil hanya path tanpa domain
    const links = document.querySelectorAll("ul#sidebar-menu a");

    links.forEach(function (link) {
        const linkPath = new URL(link.href).pathname;

        // kalau currentUrl persis sama atau currentUrl masih dalam linkPath (contoh: /menu & /menu/create)
        if (currentUrl === linkPath || currentUrl.startsWith(linkPath + "/")) {
            link.classList.add("active-page");

            let parent = link.parentElement;
            parent.classList.add("active-page");

            while (parent && parent.tagName !== "BODY") {
                if (parent.tagName === "LI") {
                    parent.classList.add("show", "open");
                }
                parent = parent.parentElement;
            }
        }
    });
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
document.addEventListener("livewire:navigated", function () {
    // console.log("appjs");

    initSidebarDropdown();
    initSidebarToggle();
    initMobileSidebar();
    highlightActiveMenu();
    initThemeToggle();
});
