// main.js

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Menu Toggle
    const navToggle = document.querySelector('.nav-toggle'); // Assume you add this class to a button in header.php
    const navMenu = document.querySelector('.navigation-menu'); // Assume this is your main navigation list

    if (navToggle && navMenu) {
        navToggle.addEventListener('click', () => {
            // Toggle an 'is-open' class on the menu and the button itself
            navMenu.classList.toggle('is-open');
            navToggle.classList.toggle('is-active');
        });
    }

    // You can add your other functions here...
    // e.g., initImagePreview();
    // e.g., initFormValidation();
});