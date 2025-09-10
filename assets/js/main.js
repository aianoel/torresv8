// Main JavaScript file for Hotel Management System

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Hotel Management System loaded');
    
    // Initialize any interactive elements
    initializeComponents();
});

function initializeComponents() {
    // Add any custom JavaScript functionality here
    
    // Example: Smooth scrolling for navigation links
    const navLinks = document.querySelectorAll('a[href^="#"]');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });
}

// Login form validation (if on login page)
if (document.getElementById('loginForm')) {
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;
        
        if (!username || !password) {
            e.preventDefault();
            alert('Please enter both username and password.');
        }
    });
}