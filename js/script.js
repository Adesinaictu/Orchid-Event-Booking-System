// js/index.js (Your main script for index.html)

document.addEventListener('DOMContentLoaded', () => {
    // --- DOM Element References ---
    const eventListDiv = document.getElementById('event-list');
    const searchInput = document.getElementById('searchInput');
    const searchButton = document.getElementById('searchButton');
    const locationFilter = document.getElementById('locationFilter');
    const dateFilter = document.getElementById('dateFilter');
    const categoryFilter = document.getElementById('categoryFilter');

    // --- API Endpoint Configuration ---
    // This API endpoint is responsible for fetching events, potentially with filters.
    // Make sure your backend (api.php) can handle 'search', 'location', 'date', 'category' GET parameters.
    const EVENTS_API_URL = 'http://localhost/orchid/api/api.php';
    const ADD_TO_CART_API_URL = 'http://localhost/orchid/api/add_to_cart.php';


    // --- Utility Function: Show Loading State ---
    // Displays a spinner and message in the event list area.
    function showLoading() {
        if (eventListDiv) {
            eventListDiv.innerHTML = `
                <div class="text-center py-5 col-12">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading events...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading amazing events...</p>
                </div>
            `;
        }
    }

    // --- Utility Function: Show Error Message ---
    // Displays an error message in the event list area if fetching fails.
    function showErrorMessage(message) {
        if (eventListDiv) {
            eventListDiv.innerHTML = `
                <div class="text-center py-5 col-12">
                    <p class="text-danger fw-bold">Error: ${message}</p>
                    <p class="text-muted">Please try refreshing the page or contact support.</p>
                </div>
            `;
        }
    }

    // --- Function to Fetch and Display Events ---
    // Sends a request to the backend API with current filter parameters.
    async function fetchEvents(filters = {}) {
        if (!eventListDiv) {
            console.warn("event-list div (ID 'event-list') not found. Not fetching or displaying events.");
            return;
        }

        showLoading(); // Show loading indicator before fetch

        const params = new URLSearchParams();
        // Append filter parameters to URLSearchParams object if they exist and are not 'all' or empty
        if (filters.search) params.append('search', filters.search);
        if (filters.location && filters.location !== 'all') params.append('location', filters.location);
        if (filters.date && filters.date !== '') params.append('date', filters.date);
        if (filters.category && filters.category !== 'all') params.append('category', filters.category);

        const apiUrl = `${EVENTS_API_URL}?${params.toString()}`;
        console.log('Fetching events from URL:', apiUrl);

        try {
            const response = await fetch(apiUrl);
            if (!response.ok) {
                // Attempt to read error details from the response body
                const errorBody = await response.text().catch(() => 'No additional error details.');
                throw new Error(`HTTP error! Status: ${response.status}. Response: ${errorBody}`);
            }
            const events = await response.json();
            console.log('Events received from API:', events);

            // Ensure the response is an array before displaying
            if (!Array.isArray(events)) {
                console.error('API response is not an array:', events);
                showErrorMessage('Invalid data format received from server.');
                return;
            }

            displayEvents(events); // Render the fetched events
        } catch (error) {
            console.error('Error fetching events:', error);
            showErrorMessage(`Failed to load events. (${error.message})`);
        }
    }

    // --- Function to Display Events ---
    // Renders the provided array of event objects into the DOM.
    function displayEvents(events) {
        if (!eventListDiv) return; // Defensive check

        eventListDiv.innerHTML = ''; // Clear previous events

        if (events.length === 0) {
            eventListDiv.innerHTML = `
                <p class="text-center py-5 col-12 text-muted">
                    No events found matching your current search and filter criteria.
                </p>
            `;
            return;
        }

        events.forEach(event => {
            // Sanitize and provide default values for event properties
            const eventId = event.id || '';
            const eventName = event.name || 'Untitled Event';
            const eventDescription = event.description || 'No description available.';
            const eventDate = event.date ? new Date(event.date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'Date TBD';
            const eventTime = event.time || 'Time TBD';
            const eventLocation = event.location || 'Location TBD';
            const eventCategory = event.category || 'N/A';
            const eventPrice = parseFloat(event.price || event.Price || 0); // Handles 'price' or 'Price' from backend
            const formattedPrice = !isNaN(eventPrice) ? `₦${eventPrice.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` : 'Price TBD';
            const imageUrl = event.image_url && event.image_url !== 'null' ? event.image_url : 'img/placeholder.jpg'; // Path from HTML root

            // Truncate description for card view
            const truncatedDescription = eventDescription.length > 100 ?
                eventDescription.substring(0, 97) + '...' :
                eventDescription;

            const eventCardHtml = `
                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <img src="${imageUrl}" class="card-img-top event-card-img" alt="${eventName}" onerror="this.onerror=null;this.src='img/placeholder.jpg';">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">${eventName}</h5>
                            <p class="card-text text-muted small mb-1"><i class="far fa-calendar-alt me-1"></i> ${eventDate} at ${eventTime}</p>
                            <p class="card-text text-muted small mb-2"><i class="fas fa-map-marker-alt me-1"></i> ${eventLocation}</p>
                            <p class="card-text fw-bold mb-2">${formattedPrice}</p>
                            <p class="card-text flex-grow-1">${truncatedDescription}</p>
                            <div class="mt-auto d-flex flex-column">
                                <a href="event-detail.html?id=${eventId}" class="btn btn-primary btn-sm mb-2">View Details</a>
                                <button class="btn btn-success btn-sm add-to-cart-btn-index"
                                    data-id="${eventId}"
                                    data-name="${eventName}"
                                    data-price="${eventPrice}">
                                    <i class="fas fa-cart-plus me-1"></i> Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            eventListDiv.insertAdjacentHTML('beforeend', eventCardHtml);
        });

        // After new events are displayed, re-attach event listeners for "Add to Cart" buttons
        setupAddToCartListeners();
    }

    // --- Function to Collect and Apply Filters ---
    // Gathers current values from search/filter inputs and calls fetchEvents.
    function applyFilters() {
        const filters = {
            search: searchInput ? searchInput.value.trim() : '', // Trim whitespace
            location: locationFilter ? locationFilter.value : 'all',
            date: dateFilter ? dateFilter.value : '',
            category: categoryFilter ? categoryFilter.value : 'all'
        };
        console.log('Applying filters:', filters);
        fetchEvents(filters);
    }

    // --- Event Listeners for Search and Filters ---
    // Attach listeners to trigger applyFilters when input values change.
    if (searchButton) {
        searchButton.addEventListener('click', applyFilters);
    } else {
        console.warn("Search button (id='searchButton') not found.");
    }

    if (searchInput) {
        // Trigger filter on every input change for live search
        searchInput.addEventListener('input', applyFilters);
        // Also allow pressing Enter in the search input to trigger search
        searchInput.addEventListener('keyup', (event) => {
            if (event.key === 'Enter') {
                applyFilters();
            }
        });
    } else {
        console.warn("Search input (id='searchInput') not found.");
    }

    if (locationFilter) {
        locationFilter.addEventListener('change', applyFilters);
    } else {
        console.warn("Location filter (id='locationFilter') not found.");
    }

    if (dateFilter) {
        dateFilter.addEventListener('change', applyFilters);
    } else {
        console.warn("Date filter (id='dateFilter') not found.");
    }

    if (categoryFilter) {
        categoryFilter.addEventListener('change', applyFilters);
    } else {
        console.warn("Category filter (id='categoryFilter') not found.");
    }

    // --- Add to Cart Logic ---
    // Function to set up event listeners for dynamically added "Add to Cart" buttons.
    function setupAddToCartListeners() {
        // Select all "Add to Cart" buttons that were just rendered
        document.querySelectorAll('.add-to-cart-btn-index').forEach(button => {
            // Crucial: Remove any existing listeners to prevent multiple calls if this function is run again
            button.removeEventListener('click', handleAddToCartClick);
            // Add the new listener
            button.addEventListener('click', handleAddToCartClick);
        });
    }

    // Event handler for "Add to Cart" button clicks.
    async function handleAddToCartClick(e) {
        // Get data attributes from the clicked button
        const id = e.currentTarget.dataset.id;
        const name = e.currentTarget.dataset.name;
        const price = e.currentTarget.dataset.price;
        const quantity = 1; // Always add 1 from the index page

        if (!id || !name || !price) {
            console.error('Missing data attributes for add to cart:', e.currentTarget);
            alert('Could not add item to cart: Incomplete event details.');
            return;
        }

        try {
            const response = await fetch(ADD_TO_CART_API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json' // Tell backend we're sending JSON
                },
                body: JSON.stringify({
                    eventId: id,
                    quantity: quantity,
                    price: price // Pass price to backend for storage/verification
                })
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({ message: 'Server error, no detailed message.' }));
                throw new Error(`Failed to add to cart: HTTP status ${response.status} - ${errorData.message}`);
            }

            const result = await response.json();

            if (result.success) {
                alert(`${quantity} ticket(s) for "${name}" added to cart!`);
                // Call the global updateCartCount function (from updateCartCount.js)
                if (typeof updateCartCount === 'function') {
                    updateCartCount();
                } else {
                    console.warn('updateCartCount function not found. Cart badge might not update.');
                }
            } else {
                alert('Failed to add to cart: ' + (result.message || 'Unknown error.'));
                console.error('Backend add to cart error:', result.message);
            }
        } catch (error) {
            console.error('Client-side add to cart error:', error);
            alert('An error occurred while adding to cart. Please try again. Details: ' + error.message);
        }
    }

    // --- Initial Page Load Actions ---
    // 1. Fetch events when the page loads, applying any initial filters from URL or defaults.
    if (eventListDiv) { // Only fetch events if we are on a page with the event list div
        fetchEvents();
    } else {
        console.warn("Event listing container (ID 'event-list') not found. Skipping initial event fetch.");
    }

    // 2. Update the cart count badge immediately when the page loads.
    // This assumes `js/updateCartCount.js` is loaded *before* this script in your HTML.
    if (typeof updateCartCount === 'function') {
        updateCartCount();
    } else {
        console.warn("updateCartCount function not found. Cart badge may not update on page load.");
    }
});