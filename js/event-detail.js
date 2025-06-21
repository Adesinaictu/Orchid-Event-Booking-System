// js/event-detail.js (Suggested/Corrected structure)

document.addEventListener('DOMContentLoaded', async function() {
    const urlParams = new URLSearchParams(window.location.search);
    const eventId = urlParams.get('id');

    const eventTitleElement = document.getElementById('event-title');
    const eventCategoryElement = document.getElementById('event-category');
    const eventDescriptionElement = document.getElementById('event-description');
    const eventDateElement = document.getElementById('event-date');
    const eventTimeElement = document.getElementById('event-time');
    const eventLocationElement = document.getElementById('event-location');
    const eventPriceElement = document.getElementById('event-price');
    const eventImageElement = document.getElementById('event-image');
    const quantityInput = document.getElementById('quantity');
    const addToCartBtn = document.getElementById('addToCartBtn');
    const eventNameBreadcrumb = document.getElementById('event-name-breadcrumb');

    let currentEventData = null; // Store event data to easily access price, etc.

    if (eventId) {
        try {
            // Fetch event details from the backend
            const response = await fetch(`http://localhost/orchid/api/event_details.php?id=${eventId}`);
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP error! status: ${response.status}. Details: ${errorText}`);
            }
            const result = await response.json();

            if (result.success && result.data) {
                currentEventData = result.data; // Store the fetched data
                eventTitleElement.textContent = currentEventData.name;
                eventNameBreadcrumb.textContent = currentEventData.name; // Update breadcrumb
                eventCategoryElement.textContent = `Category: ${currentEventData.category_name}`;
                eventDescriptionElement.textContent = currentEventData.description;
                eventDateElement.textContent = currentEventData.date;
                eventTimeElement.textContent = currentEventData.time;
                eventLocationElement.textContent = currentEventData.location;
                eventPriceElement.textContent = parseFloat(currentEventData.price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                eventImageElement.src = currentEventData.image_url || 'https://via.placeholder.com/800x450?text=No+Image';

                // Set max quantity based on available tickets
                if (currentEventData.available_tickets !== undefined) {
                    quantityInput.max = currentEventData.available_tickets;
                    quantityInput.min = 1; // Ensure minimum is 1
                    if (parseInt(quantityInput.value) > currentEventData.available_tickets) {
                         quantityInput.value = currentEventData.available_tickets; // Adjust if default is too high
                    }
                    if (currentEventData.available_tickets === 0) {
                        addToCartBtn.disabled = true;
                        addToCartBtn.textContent = 'Sold Out';
                        addToCartBtn.classList.remove('btn-primary'); // Assuming default is btn-primary
                        addToCartBtn.classList.add('btn-secondary');
                    } else {
                        addToCartBtn.disabled = false;
                        addToCartBtn.textContent = 'Add to Cart';
                        addToCartBtn.classList.add('btn-primary');
                        addToCartBtn.classList.remove('btn-secondary');
                    }
                }

            } else {
                eventTitleElement.textContent = 'Event not found.';
                alert('Event not found: ' + (result.message || 'Unknown error.'));
            }
        } catch (error) {
            console.error('Error fetching event details:', error);
            eventTitleElement.textContent = 'Error loading event.';
            alert('An error occurred while loading event details. Please try again.');
        }
    } else {
        eventTitleElement.textContent = 'No event ID provided.';
    }


    // --- Add to Cart Button Logic ---
    addToCartBtn.addEventListener('click', async function() {
        if (!currentEventData) {
            alert('Event data not loaded. Please try again.');
            return;
        }

        const quantity = parseInt(quantityInput.value);
        if (isNaN(quantity) || quantity < 1) {
            alert('Please enter a valid quantity (minimum 1).');
            return;
        }

        // Optional: Client-side check against available tickets
        if (currentEventData.available_tickets !== undefined && quantity > currentEventData.available_tickets) {
            alert(`Only ${currentEventData.available_tickets} tickets available.`);
            quantityInput.value = currentEventData.available_tickets; // Adjust input
            return;
        }

        try {
            // Send eventId and quantity to your add_to_cart.php API
            const response = await fetch('http://localhost/orchid/api/update_cart_item.php', { 
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    eventId: currentEventData.id, // Use the ID from fetched data
                    quantity: quantity
                })
            });

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP error! status: ${response.status}. Details: ${errorText}`);
            }

            const result = await response.json();

            if (result.success) {
                alert(result.message); // "Item added to cart" or "Cart item quantity updated"
                // Update cart count in navbar
                if (typeof updateCartCount === 'function') {
                    updateCartCount();
                }
            } else {
                alert('Failed to add item to cart: ' + (result.message || 'Unknown error.'));
            }
        } catch (error) {
            console.error('Error adding item to cart (client-side):', error);
            alert('An error occurred while adding to cart. Please try again. Details: ' + error.message);
        }
    });

    // Initial load of cart count for the navbar badge
    if (typeof updateCartCount === 'function') {
        updateCartCount();
    }
});



