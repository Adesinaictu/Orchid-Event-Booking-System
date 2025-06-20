// js/events.js

document.addEventListener('DOMContentLoaded', function() {
    const eventsContainer = document.getElementById('events-container');
    const loadingMessage = document.getElementById('loading-message');
    const errorMessage = document.getElementById('error-message');
    const noEventsMessage = document.getElementById('no-events-message');

    // Get references to filter/search elements from index.html
    const searchInput = document.getElementById('searchInput');
    const searchButton = document.getElementById('searchButton');
    const locationFilter = document.getElementById('locationFilter');
    const dateFilter = document.getElementById('dateFilter');
    const categoryFilter = document.getElementById('categoryFilter');

    const API_BASE_URL = 'http://localhost/orchid/api/events.php'; // Base path to your PHP API endpoint

    async function fetchEvents() {
        loadingMessage.style.display = 'block'; // Show loading message
        errorMessage.style.display = 'none'; // Hide any previous errors
        noEventsMessage.style.display = 'none'; // Hide no events message
        eventsContainer.innerHTML = ''; // Clear previous content

        // Construct query parameters based on current filter values
        const params = new URLSearchParams();
        const searchText = searchInput.value.trim();
        const selectedLocation = locationFilter.value;
        const selectedDate = dateFilter.value;
        const selectedCategory = categoryFilter.value;

        if (searchText) {
            params.append('search', searchText);
        }
        // Only append if a specific location is selected (not 'all' or the disabled default)
        if (selectedLocation && selectedLocation !== 'all' && selectedLocation !== 'Filter by Location') {
            params.append('location', selectedLocation);
        }
        if (selectedDate) {
            params.append('date', selectedDate);
        }
        // Only append if a specific category is selected (not 'all' or the disabled default)
        if (selectedCategory && selectedCategory !== 'all' && selectedCategory !== 'Filter by Category') {
            params.append('category', selectedCategory);
        }

        const fullApiUrl = `${API_BASE_URL}?${params.toString()}`;
        console.log("Fetching from:", fullApiUrl); // Debugging: See the full URL being fetched

        try {
            const response = await fetch(fullApiUrl);

            if (!response.ok) {
                const errorText = await response.text(); // Get raw response for debugging
                throw new Error(`HTTP error! Status: ${response.status}, Details: ${errorText}`);
            }

            const data = await response.json(); // Attempt to parse as JSON

            console.log("API Response:", data); // Debugging: See the JSON response from PHP

            if (data.success && data.data && data.data.length > 0) {
                loadingMessage.style.display = 'none'; // Hide loading message
                data.data.forEach(event => {
                    const eventCardHtml = `
                        <div class="col">
                            <div class="card event-card h-100 shadow-sm">
                                <img src="${event.image_url}" class="card-img-top event-img" alt="${event.name}">
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title">${event.name}</h5>
                                    <p class="card-text text-muted small mb-1">
                                        <i class="fas fa-calendar-alt me-1"></i> ${event.formatted_date}
                                    </p>
                                    <p class="card-text text-muted small mb-2">
                                        <i class="fas fa-map-marker-alt me-1"></i> ${event.location}
                                    </p>
                                    <div class="d-flex justify-content-between align-items-baseline mt-auto">
                                        <p class="card-text price-text">
                                            <strong>NGN ${parseFloat(event.price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong>
                                        </p>
                                        <a href="event-detail.html?id=${event.id}" class="btn view-details-btn">View Details</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    eventsContainer.insertAdjacentHTML('beforeend', eventCardHtml);
                });
            } else {
                loadingMessage.style.display = 'none';
                noEventsMessage.style.display = 'block'; // Show no events message
                noEventsMessage.textContent = data.message || 'No events found at the moment.';
            }

        } catch (error) {
            console.error("Error fetching events:", error);
            loadingMessage.style.display = 'none';
            errorMessage.style.display = 'block'; // Show error message
            errorMessage.textContent = 'Failed to load events. Please try again later. Error details: ' + error.message;
        }
    }

    // Event Listeners for Filters and Search
    // When the search button is clicked
    searchButton.addEventListener('click', fetchEvents);
    // When Enter is pressed in the search input
    searchInput.addEventListener('keypress', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault(); // Prevent default form submission
            fetchEvents();
        }
    });
    // When a location is selected from the dropdown
    locationFilter.addEventListener('change', fetchEvents);
    // When a date is selected
    dateFilter.addEventListener('change', fetchEvents);
    // When a category is selected from the dropdown
    categoryFilter.addEventListener('change', fetchEvents);


    // Initial fetch of events when the page loads
    fetchEvents();
});