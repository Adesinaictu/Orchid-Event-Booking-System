// js/event-detail.js

document.addEventListener('DOMContentLoaded', async function() {
    const eventDetailContainer = document.getElementById('event-detail-container');
    const loadingMessage = document.getElementById('loading-message');
    const errorMessage = document.getElementById('error-message');

    // Function to show loading state
    function displayLoadingState() {
        if (loadingMessage) {
            loadingMessage.style.display = 'block';
        }
        if (errorMessage) {
            errorMessage.style.display = 'none';
        }
        if (eventDetailContainer) {
            eventDetailContainer.innerHTML = ''; // Clear previous content
        }
        // Optional: Set a placeholder image immediately
        const eventImage = document.getElementById('event-image');
        if (eventImage) {
             eventImage.src = 'https://via.placeholder.com/800x450?text=Loading...';
             eventImage.alt = 'Loading event image';
        }
    }

    // Function to display error state
    function displayErrorState(message) {
        if (loadingMessage) {
            loadingMessage.style.display = 'none';
        }
        if (errorMessage) {
            errorMessage.style.display = 'block';
            errorMessage.textContent = message;
        }
        if (eventDetailContainer) {
            eventDetailContainer.innerHTML = `<p class="alert alert-danger text-center">${message}</p>`; // Show error inside container
        }
        const eventImage = document.getElementById('event-image');
        if (eventImage) {
            eventImage.src = 'https://via.placeholder.com/800x450?text=Error';
            eventImage.alt = 'Error loading image';
        }
    }

    // Function to fetch event details
    async function fetchEventDetails() {
        displayLoadingState();

        const urlParams = new URLSearchParams(window.location.search);
        const eventId = urlParams.get('id');

        if (!eventId) {
            displayErrorState('No event ID provided in the URL.');
            return;
        }

        const API_URL = `api/event_details.php?id=${eventId}`; // Correct API endpoint

        try {
            const response = await fetch(API_URL);

            if (!response.ok) {
                const errorText = await response.text(); // Get raw response for debugging
                throw new Error(`HTTP error! Status: ${response.status}, Details: ${errorText}`);
            }

            const data = await response.json(); // Attempt to parse as JSON

            if (data.success && data.data) {
                const event = data.data;

                // Update HTML elements with event data
                document.getElementById('event-image').src = event.image_url; 
                document.getElementById('event-image').alt = event.name;
                document.getElementById('event-title').textContent = event.name;
                document.getElementById('event-date').textContent = event.formatted_date;
                document.getElementById('event-time').textContent = event.formatted_time;
                document.getElementById('event-location').textContent = event.location;
                document.getElementById('event-description').innerHTML = event.description; // Use innerHTML for rich text
                document.getElementById('event-category').textContent = event.category;
                document.getElementById('event-price').textContent = `NGN ${parseFloat(event.price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

                // Hide loading and error messages
                if (loadingMessage) loadingMessage.style.display = 'none';
                if (errorMessage) errorMessage.style.display = 'none';
                if (eventDetailContainer) eventDetailContainer.style.display = 'block'; // Show content
            } else {
                displayErrorState(data.message || 'Failed to retrieve event details.');
            }

        } catch (error) {
            console.error('Error fetching event details:', error);
            displayErrorState(`Error loading event: ${error.message}. Please try again.`);
        }
    }

    // Add to Cart functionality
    const addToCartBtn = document.getElementById('addToCartBtn');
    if (addToCartBtn) {
        addToCartBtn.addEventListener('click', function() {
            const eventId = new URLSearchParams(window.location.search).get('id');
            const eventTitle = document.getElementById('event-title').textContent;
            const eventPrice = parseFloat(document.getElementById('event-price').textContent.replace('NGN ', '').replace(/,/g, ''));
            const quantity = parseInt(document.getElementById('quantity').value);
            const eventImageUrl = document.getElementById('event-image').src; // Get the image URL
            const eventDate = document.getElementById('event-date').textContent;     // Get the date
            const eventLocation = document.getElementById('event-location').textContent; // Get the location

            if (!eventId || isNaN(eventPrice) || isNaN(quantity) || quantity < 1) {
                alert('Please select a valid quantity.');
                return;
            }

            let cart = JSON.parse(localStorage.getItem('cart')) || [];

            const existingItemIndex = cart.findIndex(item => item.id === eventId);

            if (existingItemIndex > -1) {
                cart[existingItemIndex].quantity += quantity;
            } else {
                cart.push({
                    id: eventId,
                    name: eventTitle,
                    price: eventPrice,
                    quantity: quantity,
                    image_url: eventImageUrl,
                    date: eventDate,
                    location: eventLocation
                });
            }

            localStorage.setItem('cart', JSON.stringify(cart));
            alert(`${quantity} ticket(s) for "${eventTitle}" added to cart!`);

            // Check if updateCartCount is globally available before calling
            if (typeof updateCartCount === 'function') {
                updateCartCount(); // Update cart badge
            } else {
                console.warn('updateCartCount function not found after adding to cart. Cart badge may not be accurate.');
            }
        });
    }

    // Initial fetch of event details when the page loads
    fetchEventDetails();

    // Call updateCartCount on initial load if it's available
    if (typeof updateCartCount === 'function') {
        updateCartCount();
    } else {
        console.warn('updateCartCount function not found on initial load. Cart badge may not be accurate.');
    }
});