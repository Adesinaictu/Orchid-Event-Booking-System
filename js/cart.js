// js/cart.js

document.addEventListener('DOMContentLoaded', function() {
    const cartItemsContainer = document.getElementById('cartItems');
    const emptyCartMessage = document.getElementById('emptyCartMessage');
    const cartSummary = document.getElementById('cartSummary');
    const cartSummaryList = document.getElementById('cart-summary-list');
    const totalItemsSpan = document.getElementById('totalItems');
    const totalPriceSpan = document.getElementById('totalPrice');
    const checkoutBtn = document.getElementById('checkoutBtn');
    const clearCartBtn = document.getElementById('clearCartBtn');

    let cart = []; // Array to hold cart items

    // --- Function to save cart to localStorage ---
    function saveCart() {
        localStorage.setItem('cart', JSON.stringify(cart));
        if (typeof updateCartCount === 'function') {
            updateCartCount(); // Update the cart count in the navbar
        }
    }

    // --- Function to load cart from localStorage ---
    function loadCart() {
        const storedCart = localStorage.getItem('cart');
        if (storedCart) {
            cart = JSON.parse(storedCart);
        }
        renderCart();
    }

    // --- Function to render cart items and summary ---
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
            const imageUrl = item.image_url ? item.image_url : 'https://via.placeholder.com/100x100?text=No+Image';

            // Render individual cart item card
            const itemCard = document.createElement('div');
            itemCard.className = 'card mb-3 shadow-sm cart-item-card';
            itemCard.innerHTML = `
                <div class="row g-0 align-items-center">
                    <div class="col-md-3">
                    <img src="<span class="math-inline">\{imageUrl\}" class\="img\-fluid rounded\-start cart\-item\-img" alt\="</span>{item.name}">
                    </div>
                    <div class="col-md-9">
                    <div class="card-body">
                        <h5 class="card-title"><span class="math-inline">\{item\.name\}</h5\>
<p class\="card\-text text\-muted small"\></span>{item.date || ''} at ${item.location || ''}</p>
                        <p class="card-text mb-2"><strong>Price: NGN <span class="math-inline">\{parseFloat\(item\.price\)\.toLocaleString\('en\-US', \{ minimumFractionDigits\: 2, maximumFractionDigits\: 2 \}\)\}</strong\></p\>
<div class\="d\-flex align\-items\-center mb\-2"\>
<label for\="quantity\-</span>{item.id}" class="form-label mb-0 me-2">Quantity:</label>
                            <input type="number" id="quantity-<span class="math-inline">\{item\.id\}" class\="form\-control quantity\-input"
value\="</span>{item.quantity}" min="1" data-item-id="<span class="math-inline">\{item\.id\}" style\="width\: 70px;"\>
</div\>
<button class\="btn btn\-danger btn\-sm remove\-item\-btn" data\-item\-id\="</span>{item.id}">
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

    // --- Event handler for quantity input change ---
    function updateItemQuantity(event) {
        const itemId = event.target.dataset.itemId;
        const newQuantity = parseInt(event.target.value);

        const itemIndex = cart.findIndex(item => item.id === itemId);
        if (itemIndex > -1 && newQuantity > 0) {
            cart[itemIndex].quantity = newQuantity;
            saveCart();
            renderCart(); // Re-render to update totals and summary
        } else if (newQuantity <= 0) {
            // If quantity goes to 0 or less, remove the item
            removeItem({ target: { dataset: { itemId: itemId } } });
        }
    }

    // --- Event handler for removing an item ---
    function removeItem(event) {
        const itemId = event.target.dataset.itemId;
        cart = cart.filter(item => item.id !== itemId);
        saveCart();
        renderCart(); // Re-render to update the display
    }

    // --- Event listener for Clear Cart button ---
    clearCartBtn.addEventListener('click', function() {
        if (confirm('Are you sure you want to clear your entire cart?')) {
            cart = []; // Empty the cart array
            saveCart(); // Save the empty cart
            renderCart(); // Re-render the display
            alert('Your cart has been cleared.');
        }
    });

    // --- Event listener for Checkout button ---
    checkoutBtn.addEventListener('click', function() {
        if (cart.length > 0) {
            // Before redirecting, you might want to send the cart data to the server
            // or confirm the user is ready.
            window.location.href = 'checkout.html'; // Redirect to the checkout page
        } else {
            alert('Your cart is empty. Please add items before proceeding to checkout.');
        }
    });

    // --- Initial Load ---
    loadCart();
});