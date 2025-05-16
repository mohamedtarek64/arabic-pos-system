// Main Sidebar functionality
document.addEventListener('DOMContentLoaded', function() {
    initSidebar();
    initCalendar();
    initTabs();
    initCharts();
    initSalesTables();
    
    // Initial data load
    if (typeof updateDashboardData === 'function') {
        updateDashboardData();
    }
});

// Initialize sidebar with mobile responsiveness
function initSidebar() {
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.getElementById('sidebar');
    
    if (!menuToggle || !sidebar) return;
    
    // Create overlay element
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);
    
    // Set initial state based on viewport width
    if (window.innerWidth > 991) {
        sidebar.classList.add('open');
        document.body.classList.add('sidebar-open');
    } else {
        sidebar.classList.remove('open');
        document.body.classList.remove('sidebar-open');
    }
    
    menuToggle.addEventListener('click', function(e) {
        e.preventDefault();
        sidebar.classList.toggle('open');
        document.body.classList.toggle('sidebar-open');
        
        // Toggle overlay
        if (sidebar.classList.contains('open') && window.innerWidth <= 991) {
            overlay.classList.add('active');
        } else {
            overlay.classList.remove('active');
        }
    });
    
    // Close sidebar when clicking on overlay
    overlay.addEventListener('click', function() {
        sidebar.classList.remove('open');
        document.body.classList.remove('sidebar-open');
        overlay.classList.remove('active');
    });
    
    // Handle dropdown menus in the sidebar
    const dropdowns = document.querySelectorAll('#sidebar .dropdown > a');
    
    dropdowns.forEach(dropdown => {
        dropdown.addEventListener('click', function(e) {
            e.preventDefault();
            
            const parent = this.parentElement;
            const dropdownMenu = parent.querySelector('.dropdown-menu');
            
            // Toggle active class on the clicked dropdown
            parent.classList.toggle('active');
            
            // Toggle dropdown menu display
            if (dropdownMenu) {
                if (dropdownMenu.style.display === 'block') {
                    dropdownMenu.style.display = 'none';
                    const arrow = this.querySelector('.menu-arrow i');
                    if (arrow) arrow.style.transform = '';
                } else {
                    dropdownMenu.style.display = 'block';
                    const arrow = this.querySelector('.menu-arrow i');
                    if (arrow) arrow.style.transform = 'rotate(-90deg)';
                }
            }
        });
    });
    
    // Initialize active dropdowns
    const activeDropdowns = document.querySelectorAll('#sidebar .dropdown.active');
    activeDropdowns.forEach(dropdown => {
        const dropdownMenu = dropdown.querySelector('.dropdown-menu');
        if (dropdownMenu) {
            dropdownMenu.style.display = 'block';
            const arrow = dropdown.querySelector('.menu-arrow i');
            if (arrow) arrow.style.transform = 'rotate(-90deg)';
        }
    });
    
    // Responsive sidebar behavior
    window.addEventListener('resize', function() {
        if (window.innerWidth <= 991) {
            if (sidebar.classList.contains('open')) {
                overlay.classList.add('active');
            }
        } else {
            sidebar.classList.add('open');
            overlay.classList.remove('active');
        }
    });
}

// Initialize calendar functionality
function initCalendar() {
    const calendarToggleBtn = document.querySelector('.calendar-toggle-btn');
    const calendarPopup = document.querySelector('.calendar-popup');
    const calendarCloseBtn = document.getElementById('calendar-close');
    
    if (!calendarToggleBtn || !calendarPopup) return;
    
    calendarToggleBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (calendarPopup.style.display === 'block') {
            calendarPopup.style.display = 'none';
        } else {
            calendarPopup.style.display = 'block';
            renderCalendar();
        }
    });
    
    // Close calendar when clicking outside
    document.addEventListener('click', function(e) {
        if (calendarPopup.style.display === 'block' && 
            !calendarToggleBtn.contains(e.target) && 
            !calendarPopup.contains(e.target)) {
            calendarPopup.style.display = 'none';
        }
    });
    
    // Calendar close button
    if (calendarCloseBtn) {
        calendarCloseBtn.addEventListener('click', function() {
            calendarPopup.style.display = 'none';
        });
    }
    
    // Render calendar with current month
    function renderCalendar() {
        const calendarBody = document.getElementById('calendar-body');
        const calendarMonth = document.getElementById('calendar-month');
        const prevBtn = document.getElementById('calendar-prev');
        const nextBtn = document.getElementById('calendar-next');
        
        if (!calendarBody || !calendarMonth || !prevBtn || !nextBtn) return;
        
        let currentDate = new Date();
        
        // Update calendar header
        function updateCalendarHeader() {
            const months = ['يناير', 'فبراير', 'مارس', 'إبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
            calendarMonth.textContent = months[currentDate.getMonth()] + ' ' + currentDate.getFullYear();
        }
        
        // Generate calendar days
        function generateCalendar() {
            calendarBody.innerHTML = '';
            
            // Get first day of month
            const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
            const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
            
            let startingDay = firstDay.getDay();
            if (startingDay === 0) startingDay = 7; // Make Sunday the 7th day
            
            const daysInMonth = lastDay.getDate();
            
            let date = 1;
            let calendarHTML = '';
            
            // Create weeks
            for (let i = 0; i < 6; i++) {
                if (date > daysInMonth) break;
                
                calendarHTML += '<tr>';
                
                // Create days in a week
                for (let j = 0; j < 7; j++) {
                    if (i === 0 && j < startingDay - 1) {
                        // Empty cells before first day of month
                        calendarHTML += '<td class="empty"></td>';
                    } else if (date > daysInMonth) {
                        // Empty cells after last day of month
                        calendarHTML += '<td class="empty"></td>';
                    } else {
                        // Determine if this date is today
                        const today = new Date();
                        const isToday = date === today.getDate() && 
                                       currentDate.getMonth() === today.getMonth() && 
                                       currentDate.getFullYear() === today.getFullYear();
                        
                        if (isToday) {
                            calendarHTML += `<td class="today"><div class="calendar-day-content">${date}</div></td>`;
                        } else {
                            calendarHTML += `<td><div class="calendar-day-content">${date}</div></td>`;
                        }
                        date++;
                    }
                }
                
                calendarHTML += '</tr>';
            }
            
            calendarBody.innerHTML = calendarHTML;
            
            // Add click events to dates
            const allDates = calendarBody.querySelectorAll('td:not(.empty)');
            allDates.forEach(dateCell => {
                dateCell.addEventListener('click', function() {
                    const selectedDay = this.textContent.trim();
                    const selectedMonth = currentDate.getMonth() + 1;
                    const selectedYear = currentDate.getFullYear();
                    
                    // Format date
                    const formattedDate = `${selectedDay.padStart(2, '0')}/${selectedMonth.toString().padStart(2, '0')}/${selectedYear}`;
                    
                    // Update date input
                    const dateInput = document.querySelector('.date-range');
                    if (dateInput) {
                        dateInput.value = formattedDate;
                    }
                    
                    // Close calendar
                    calendarPopup.style.display = 'none';
                    
                    // Load data for selected date
                    updateDashboardForDate(formattedDate);
                });
            });
        }
        
        // Initialize
        updateCalendarHeader();
        generateCalendar();
        
        // Navigation buttons
        prevBtn.addEventListener('click', function() {
            currentDate.setMonth(currentDate.getMonth() - 1);
            updateCalendarHeader();
            generateCalendar();
        });
        
        nextBtn.addEventListener('click', function() {
            currentDate.setMonth(currentDate.getMonth() + 1);
            updateCalendarHeader();
            generateCalendar();
        });
    }
}

// Function to update dashboard data based on selected date
function updateDashboardForDate(dateStr) {
    console.log('Updating dashboard for date: ' + dateStr);
    
    // Make an AJAX request to get dashboard data for the selected date
    fetch('dashboard-data.php?date=' + dateStr)
        .then(response => response.json())
        .then(data => {
            console.log('Received data:', data);
            
            // Update the dashboard UI with the new data
            if (data.status === 'success') {
                updateDashboardUI(data.data);
            }
        })
        .catch(error => {
            console.error('Error fetching dashboard data:', error);
        });
}

// Function to update the dashboard UI with new data
function updateDashboardUI(data) {
    // Update inventory value
    const inventoryValue = document.querySelector('.card-title:contains("قيمة المخزون اليوم")').closest('.card').querySelector('.value');
    if (inventoryValue && data.inventory_value !== undefined) {
        inventoryValue.innerHTML = parseFloat(data.inventory_value).toLocaleString() + ' <span>جنيه</span>';
    }
    
    // Update collected amounts
    if (data.collected_amounts) {
        // Update the collected amounts card
    }
    
    // Update other dashboard elements
}

// Initialize tabs in the dashboard
function initTabs() {
    const tabItems = document.querySelectorAll('.tabs-list .tab-item');
    if (tabItems.length === 0) return;
    
    tabItems.forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.tabs-list .tab-item').forEach(t => {
                t.classList.remove('active');
            });
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Show corresponding tab content
            const tabId = this.getAttribute('data-tab');
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            const tabContent = document.getElementById(tabId);
            if (tabContent) {
                tabContent.classList.add('active');
            }
        });
    });
}

// Initialize charts if they exist
function initCharts() {
    if (typeof Chart === 'undefined') return;
    
    // Initialize Sales Chart
    const salesChartCanvas = document.getElementById('salesChart');
    if (salesChartCanvas) {
        // Fetch real sales data from server
        fetch('dashboard-data.php?chart=sales')
            .then(response => response.json())
            .then(chartData => {
                // Set up chart with real data from database
                const ctx = salesChartCanvas.getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'],
                        datasets: [{
                            label: 'المبيعات الشهرية',
                            data: chartData.data || [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                            backgroundColor: 'rgba(33, 150, 243, 0.7)',
                            borderColor: '#1976d2',
                            borderRadius: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            },
                            title: {
                                display: true,
                                text: 'إحصائيات المبيعات الشهرية',
                                color: '#1565c0'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            })
            .catch(error => {
                console.error('Error loading chart data:', error);
                
                // Fallback to empty chart
                const ctx = salesChartCanvas.getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'],
                        datasets: [{
                            label: 'المبيعات الشهرية',
                            data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                            backgroundColor: 'rgba(33, 150, 243, 0.7)',
                            borderColor: '#1976d2',
                            borderRadius: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            });
    }
    
    // Initialize Monthly Sales Chart
    const monthlyChartCanvas = document.getElementById('salesMonthlyChart');
    if (monthlyChartCanvas) {
        // Similar implementation as above
    }
}

// Initialize sales tables
function initSalesTables() {
    // Check if tables exist
    const recentSalesTable = document.querySelector('.recent-sales-table');
    const recentActivitiesTable = document.querySelector('.recent-table');
    
    // Add event listeners to view all buttons
    const viewAllButtons = document.querySelectorAll('.view-all-btn');
    viewAllButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const href = this.getAttribute('href') || this.closest('a').getAttribute('href');
            if (href) {
                window.location.href = href;
            }
        });
    });
} 