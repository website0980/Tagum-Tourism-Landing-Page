// Events Calendar JavaScript
document.addEventListener('DOMContentLoaded', function() {

    // Calendar state
    let currentDate = new Date();
    let selectedDate = null;
    let currentCategory = 'all';
    let searchQuery = '';

    // DOM elements
    const calendarGrid = document.getElementById('calendarGrid');
    const calendarTitle = document.getElementById('calendarTitle');
    const prevMonthBtn = document.getElementById('prevMonth');
    const nextMonthBtn = document.getElementById('nextMonth');
    const eventsGrid = document.getElementById('eventsGrid');
    const eventSearch = document.getElementById('eventSearch');
    const categoryFilters = document.querySelectorAll('.category-filter');

    // Month names
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                       'July', 'August', 'September', 'October', 'November', 'December'];

    // Initialize calendar
    function initCalendar() {
        if (!calendarGrid || !calendarTitle) {
            console.error('Calendar elements not found');
            return;
        }
        renderCalendar(currentDate);
        setupEventListeners();
    }
    
    // Render calendar grid
    function renderCalendar(date) {
        const year = date.getFullYear();
        const month = date.getMonth();
        
        // Update title
        calendarTitle.textContent = `${monthNames[month]} ${year}`;
        
        // Clear grid
        calendarGrid.innerHTML = '';
        
        // Get first day of month and total days
        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const daysInPrevMonth = new Date(year, month, 0).getDate();
        
        // Get dates that have events
        const eventDates = getEventDates();
        
        // Previous month days
        for (let i = firstDay - 1; i >= 0; i--) {
            const day = daysInPrevMonth - i;
            const dayElement = createDayElement(day, true, false);
            calendarGrid.appendChild(dayElement);
        }
        
        // Current month days
        const today = new Date();
        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const isToday = today.getDate() === day && 
                           today.getMonth() === month && 
                           today.getFullYear() === year;
            const hasEvent = eventDates.includes(dateStr);
            const isSelected = selectedDate === dateStr;
            
            const dayElement = createDayElement(day, false, isToday, hasEvent, isSelected, dateStr);
            calendarGrid.appendChild(dayElement);
        }
        
        // Next month days
        const totalCells = calendarGrid.children.length;
        const remainingCells = 42 - totalCells; // 6 rows x 7 days
        for (let day = 1; day <= remainingCells; day++) {
            const dayElement = createDayElement(day, true, false);
            calendarGrid.appendChild(dayElement);
        }
    }
    
    // Create day element
    function createDayElement(day, isOtherMonth, isToday, hasEvent = false, isSelected = false, dateStr = '') {
        const dayElement = document.createElement('div');
        dayElement.className = 'calendar-day';
        dayElement.textContent = day;
        
        if (isOtherMonth) {
            dayElement.classList.add('other-month');
        }
        
        if (isToday) {
            dayElement.classList.add('today');
        }
        
        if (hasEvent) {
            dayElement.classList.add('has-event');
        }
        
        if (isSelected) {
            dayElement.classList.add('selected');
        }
        
        if (!isOtherMonth && dateStr) {
            dayElement.addEventListener('click', () => selectDate(dateStr));
        }
        
        return dayElement;
    }
    
    // Get dates that have events
    function getEventDates() {
        if (!eventsData || !Array.isArray(eventsData)) return [];

        return eventsData
            .filter(event => event.date)
            .map(event => {
                // Handle different date formats
                const dateStr = event.date;
                if (dateStr.includes(' ')) {
                    // Try to parse the date and format it as YYYY-MM-DD
                    const parsedDate = new Date(dateStr);
                    if (!isNaN(parsedDate.getTime())) {
                        const year = parsedDate.getFullYear();
                        const month = String(parsedDate.getMonth() + 1).padStart(2, '0');
                        const day = String(parsedDate.getDate()).padStart(2, '0');
                        return `${year}-${month}-${day}`;
                    }
                    // If parsing fails, return the first part
                    return dateStr.split(' ')[0];
                }
                return dateStr;
            });
    }
    
    // Select date
    function selectDate(dateStr) {
        selectedDate = selectedDate === dateStr ? null : dateStr;
        renderCalendar(currentDate);
        filterEvents();
    }
    
    // Navigate months
    function navigateMonth(direction) {
        if (direction === 'prev') {
            currentDate.setMonth(currentDate.getMonth() - 1);
        } else {
            currentDate.setMonth(currentDate.getMonth() + 1);
        }
        renderCalendar(currentDate);
    }
    
    // Filter events
    function filterEvents() {
        if (!eventsData || !Array.isArray(eventsData)) return;
        
        const eventCards = document.querySelectorAll('.event-card');
        
        eventCards.forEach(card => {
            const cardDate = card.dataset.date;
            const cardCategory = card.dataset.category;
            const title = card.querySelector('.event-title').textContent.toLowerCase();
            const location = card.querySelector('.event-location')?.textContent.toLowerCase() || '';
            const description = card.querySelector('.event-description')?.textContent.toLowerCase() || '';
            
            // Check date filter
            let dateMatch = true;
            if (selectedDate) {
                dateMatch = cardDate === selectedDate;
            }
            
            // Check category filter
            let categoryMatch = currentCategory === 'all' || cardCategory === currentCategory;
            
            // Check search filter
            let searchMatch = true;
            if (searchQuery) {
                const searchText = `${title} ${location} ${description}`;
                searchMatch = searchText.includes(searchQuery.toLowerCase());
            }
            
            // Show/hide card
            if (dateMatch && categoryMatch && searchMatch) {
                card.style.display = 'flex';
                card.style.animation = 'fadeIn 0.4s ease-out';
            } else {
                card.style.display = 'none';
            }
        });
        
        // Check if any events are visible
        const visibleCards = Array.from(eventCards).filter(card => card.style.display !== 'none');
        const noEventsMessage = document.querySelector('.no-events-message');
        
        if (visibleCards.length === 0 && noEventsMessage) {
            noEventsMessage.style.display = 'block';
        } else if (noEventsMessage) {
            noEventsMessage.style.display = 'none';
        }
    }
    
    // Setup event listeners
    function setupEventListeners() {
        // Month navigation
        if (prevMonthBtn) {
            prevMonthBtn.addEventListener('click', () => navigateMonth('prev'));
        }
        if (nextMonthBtn) {
            nextMonthBtn.addEventListener('click', () => navigateMonth('next'));
        }

        // Search
        if (eventSearch) {
            eventSearch.addEventListener('input', (e) => {
                searchQuery = e.target.value.trim();
                filterEvents();
            });
        }

        // Category filters
        categoryFilters.forEach(filter => {
            filter.addEventListener('click', () => {
                // Update active state
                categoryFilters.forEach(f => f.classList.remove('active'));
                filter.classList.add('active');

                // Update category
                currentCategory = filter.dataset.category;
                filterEvents();
            });
        });
    }
    
    // Initialize
    initCalendar();
});
