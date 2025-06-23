//  for Database Integration

document.addEventListener('DOMContentLoaded', function() {
    const cartItemsContainer = document.getElementById('cartItems');

    // --- CRITICAL FIX START ---
    // Only proceed with cart functionality if the main cart container element exists on this page.
    // This prevents errors on pages like index.html where full cart UI might not be present.
    if (!cartItemsContainer) {
        console.log("Cart container (ID 'cartItems') not found on this page. Skipping cart.js initialization.");
        // Optionally, you might still want to call updateCartCount if it's a global counter in the header
        if (typeof updateCartCount === 'function') {
            // Fetch cart to update the count in the header, but don't try to render the full cart UI
            fetch('http://localhost/orchid/api/get_cart.php')
                .then(response => response.json())
                .then(result => {
                    if (result.success && result.data && Array.isArray(result.data.cart)) {
                        updateCartCount(result.data.cart.reduce((sum, item) => sum + item.quantity, 0));
                    }
                })
                .catch(error => console.error('Error fetching cart count:', error));
        }
        return; // Exit the function if the cart UI elements aren't present
    }
    // --- CRITICAL FIX END ---


    const emptyCartMessage = document.getElementById('emptyCartMessage');
    const cartSummary = document.getElementById('cartSummary');
    const cartSummaryList = document.getElementById('cart-summary-list');
    const totalItemsSpan = document.getElementById('totalItems');
    const totalPriceSpan = document.getElementById('totalPrice');
    const checkoutBtn = document.getElementById('checkoutBtn');
    const clearCartBtn = document.getElementById('clearCartBtn');

    // --- API Endpoints ---
    const GET_CART_API_URL = 'http://localhost/orchid/api/get_cart.php';
    const UPDATE_CART_API_URL = 'http://localhost/orchid/api/update_cart_item.php';
    const REMOVE_CART_API_URL = 'http://localhost/orchid/api/remove_from_cart.php';
    const CLEAR_ALL_CART_API_URL = 'http://localhost/orchid/api/clear_cart.php';

    let cart = []; // Array to hold cart items (will be populated from the API)

    // --- Function to fetch and render cart from backend (replaces loadCart and saveCart logic) ---
    async function loadCartAndRender() {
        try {
            const response = await fetch(GET_CART_API_URL);
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP error! status: ${response.status}. Details: ${errorText}`);
            }
            const result = await response.json();

            if (result.success) {
                // Access the nested 'cart' array as confirmed by API response
                cart = result.data.cart;

                // Basic validation: ensure cart is an array before proceeding
                if (!Array.isArray(cart)) {
                    console.error('API did not return an array for cart:', cart);
                    cart = []; // Reset to empty array to prevent further errors
                }

                renderCart(); // Render the cart items fetched from the API
                // Assuming updateCartCount is loaded from updateCartCount.js
                if (typeof updateCartCount === 'function') {
                    updateCartCount(cart.reduce((sum, item) => sum + item.quantity, 0)); // Pass total items to updateCartCount
                }
            } else {
                alert('Failed to load cart items from server: ' + (result.message || 'Unknown error.'));
                cart = []; // Ensure cart is empty if API call failed
                renderCart(); // Render empty cart
            }
        } catch (error) {
            console.error('Error fetching cart items from backend:', error);
            alert('An error occurred while loading your cart. Please try again.');
            cart = []; // Ensure cart is empty on error
            renderCart(); // Render empty cart
        }
    }

    // --- Function to render cart items and summary (mostly unchanged, uses the 'cart' array) ---
    function renderCart() {
        cartItemsContainer.innerHTML = ''; // Clear previous items
        cartSummaryList.innerHTML = ''; // Clear previous summary items

        if (cart.length === 0) {
            emptyCartMessage.style.display = 'block';
            cartSummary.style.display = 'none';
            checkoutBtn.disabled = true; // Disable checkout if cart is empty
            clearCartBtn.disabled = true;
            totalItemsSpan.textContent = '0';
            totalPriceSpan.textContent = '0.00';
            return;
        }

        emptyCartMessage.style.display = 'none';
        cartSummary.style.display = 'block';
        checkoutBtn.disabled = false; // Enable checkout
        clearCartBtn.disabled = false;

        let totalItems = 0;
        let totalPrice = 0;

        cart.forEach(item => {
            // Provide a fallback image if item.image_url is missing
            const imageUrl = item.image_url ? item.image_url : 'https://via.placeholder.com/100x100?text=No+Image';

            // Render individual cart item card
            const itemCard = document.createElement('div');
            itemCard.className = 'card mb-3 shadow-sm cart-item-card';
            itemCard.innerHTML = `
                <div class="row g-0 align-items-center">
                    <div class="col-md-3">
                        <img src="${imageUrl}" class="img-fluid rounded-start cart-item-img" alt="${item.name}">
                    </div>
                    <div class="col-md-9">
                        <div class="card-body">
                            <h5 class="card-title">${item.name}</h5>
                            <p class="card-text text-muted small">${item.date || ''} at ${item.location || ''}</p>
                            <p class="card-text mb-2"><strong>Price: NGN ${parseFloat(item.price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong></p>
                            <div class="d-flex align-items-center mb-2">
                                <label for="quantity-${item.event_id}" class="form-label mb-0 me-2">Quantity:</label>
                                <input type="number" id="quantity-${item.event_id}" class="form-control quantity-input"
                                    value="${item.quantity}" min="1" data-item-id="${item.event_id}" style="width: 70px;">
                            </div>
                            <button class="btn btn-danger btn-sm remove-item-btn" data-item-id="${item.event_id}">
                                <i class="fas fa-trash-alt me-1"></i> Remove
                            </button>
                        </div>
                    </div>
                </div>
            `;
            cartItemsContainer.appendChild(itemCard);

            // Render item in cart summary list
            const summaryListItem = document.createElement('li');
            summaryListItem.className = 'list-group-item d-flex justify-content-between align-items-center';
            const itemSubtotal = parseFloat(item.price) * item.quantity;
            summaryListItem.innerHTML = `
                <span>${item.name} (${item.quantity}x)</span>
                <span>NGN ${itemSubtotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
            `;
            cartSummaryList.appendChild(summaryListItem);

            totalItems += item.quantity;
            totalPrice += itemSubtotal;
        });

        totalItemsSpan.textContent = totalItems;
        totalPriceSpan.textContent = totalPrice.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        // Attach event listeners for quantity changes and remove buttons
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', updateItemQuantity);
        });
        document.querySelectorAll('.remove-item-btn').forEach(button => {
            button.addEventListener('click', removeItem);
        });
    }

    // --- Backend interaction for quantity input change ---
    async function updateItemQuantity(event) {
        const eventId = event.target.dataset.itemId; // Using eventId as per database
        const newQuantity = parseInt(event.target.value);

        if (newQuantity <= 0) {
            // If quantity goes to 0 or less, remove the item via backend
            removeItem({ target: { dataset: { itemId: eventId } } });
            return;
        }

        try {
            const response = await fetch(UPDATE_CART_API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ eventId: eventId, quantity: newQuantity })
            });

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP error! status: ${response.status}. Details: ${errorText}`);
            }

            const result = await response.json();
            if (result.success) {
                // If update successful, reload the cart from backend to reflect changes
                loadCartAndRender();
            } else {
                alert('Failed to update item quantity: ' + (result.message || 'Unknown error.'));
                // If update fails, reload to show correct state from backend (revert UI if needed)
                loadCartAndRender();
            }
        } catch (error) {
            console.error('Error updating cart item quantity:', error);
            alert('An error occurred while updating cart quantity. Please try again.');
            loadCartAndRender(); // Reload to show correct state from backend on error
        }
    }

    // --- Backend interaction for removing an item ---
    async function removeItem(event) {
        const eventId = event.target.dataset.itemId; // Using eventId as per database

        if (!confirm('Are you sure you want to remove this item from your cart?')) {
            return; // User cancelled
        }

        try {
            const response = await fetch(REMOVE_CART_API_URL, {
                method: 'POST', // Or DELETE, depending on your API design
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ eventId: eventId })
            });

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP error! status: ${response.status}. Details: ${errorText}`);
            }

            const result = await response.json();
            if (result.success) {
                // If removal successful, reload the cart from backend
                loadCartAndRender();
            } else {
                alert('Failed to remove item: ' + (result.message || 'Unknown error.'));
            }
        } catch (error) {
            console.error('Error removing cart item:', error);
            alert('An error occurred while removing the item. Please try again.');
        }
    }

    // --- Backend interaction for Clear Cart button ---
    clearCartBtn.addEventListener('click', async function() {
        if (!confirm('Are you sure you want to clear your entire cart?')) {
            return; // User cancelled
        }

        try {
            const response = await fetch(CLEAR_ALL_CART_API_URL, {
                method: 'POST', // Or DELETE, depending on your API design
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP error! status: ${response.status}. Details: ${errorText}`);
            }

            const result = await response.json();
            if (result.success) {
                cart = []; // Empty local cart array on successful backend clear
                renderCart(); // Update UI to show empty cart
                if (typeof updateCartCount === 'function') {
                    updateCartCount(0); // Set count to 0 after clearing
                }
                alert('Your cart has been cleared.');
            } else {
                alert('Failed to clear cart: ' + (result.message || 'Unknown error.'));
            }
        } catch (error) {
            console.error('Error clearing cart:', error);
            alert('An error occurred while clearing the cart. Please try again.');
        }
    });

    // --- Event listener for Checkout button (unchanged) ---
    checkoutBtn.addEventListener('click', function() {
        if (cart.length > 0) {
            window.location.href = 'checkout.html'; // Redirect to the checkout page
        } else {
            alert('Your cart is empty. Please add items before proceeding to checkout.');
        }
    });

    // --- Initial Load: Load cart from the backend when the page loads ---
    loadCartAndRender();
});