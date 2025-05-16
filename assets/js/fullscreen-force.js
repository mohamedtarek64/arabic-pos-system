/**
 * Force fullscreen mode for cashier interface
 * This script ensures the interface fills the entire screen without margins
 */
document.addEventListener('DOMContentLoaded', function() {
    // Force full width on main container
    forceFullWidth();
    
    // Also apply on resize
    window.addEventListener('resize', forceFullWidth);
    
    // Force full width every 2 seconds to handle any dynamic changes
    setInterval(forceFullWidth, 2000);
});

/**
 * Force all containers to use full width
 */
function forceFullWidth() {
    // Main container elements
    const containers = [
        '.container', 
        '.main-content', 
        '.cashier-container',
        '.top-nav',
        '.page-content'
    ];
    
    // Apply full width to all containers
    containers.forEach(selector => {
        const elements = document.querySelectorAll(selector);
        elements.forEach(el => {
            el.classList.add('force-fullwidth');
            
            // Force inline styles to override any other CSS
            el.style.width = '100vw';
            el.style.maxWidth = '100vw';
            el.style.margin = '0';
            el.style.padding = '0';
            
            // Only apply these to main containers
            if (selector === '.container' || selector === '.main-content') {
                el.style.position = 'absolute';
                el.style.left = '0';
                el.style.right = '0';
            }
        });
    });
    
    // Calculate dimensions for internal elements
    const topNav = document.querySelector('.top-nav');
    const mainContent = document.querySelector('.main-content');
    
    if (topNav && mainContent) {
        // Set main content height to viewport minus top nav
        const topNavHeight = topNav.offsetHeight;
        mainContent.style.height = `calc(100vh - ${topNavHeight}px)`;
    }
    
    // Ensure the cashier container uses available space
    const cashierContainer = document.querySelector('.cashier-container');
    if (cashierContainer && mainContent) {
        cashierContainer.style.height = '100%';
    }
    
    // Fix any scrollbar issues
    document.documentElement.style.overflowX = 'hidden';
    document.body.style.overflowX = 'hidden';
} 