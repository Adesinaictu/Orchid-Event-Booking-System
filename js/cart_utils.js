// cart_utils.js

/**
 * Updates the cart count displayed in the navigation bar/badge by reading from localStorage.
 * This is for immediate frontend feedback after client-side actions (e.g., clearing cart).
 */
function updateCartCountFromLocalStorage() {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const cartCountElement = document.getElementById('cart-count-badge'); // Ensure your HTML element has this ID

    if (cartCountElement) {
        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        cartCountElement.textContent = totalItems;
        cartCountElement.style.display = totalItems > 0 ? 'inline-block' : 'none'; // Hide if 0 items
    } else {
        console.warn("Cart count element (id='cart-count-badge') not found for localStorage update.");
    }
}


function clearCart() {
    localStorage.removeItem('cart');
    updateCartCountFromLocalStorage(); // Call the localStorage-based update
    console.log("Cart cleared from frontend (localStorage).");
}


async function updateCartCountFromServer() {
    const cartCountBadge = document.getElementById('cart-count-badge');
    const API_URL = 'api/get_cart_count.php'; // !! IMPORTANT: Verify this path !!

    if (!cartCountBadge) {
        console.warn("Cart count element (id='cart-count-badge') not found for server update.");
        return; // Exit if the element doesn't exist on the page
    }

    try {
        const response = await fetch(API_URL);

        if (!response.ok) {
            const errorDetails = await response.text();
            throw new Error(`HTTP error! status: ${response.status}, details: ${errorDetails}`);
        }

        const data = await response.json();
        if (data.success) {
            cartCountBadge.textContent = data.count;
            cartCountBadge.style.display = data.count > 0 ? 'inline-block' : 'none';
        } else {
            console.error('Error in API response for cart count:', data.message || 'Unknown error.');
            cartCountBadge.textContent = '0';
            cartCountBadge.style.display = 'none';
        }
    } catch (error) {
        console.error('Error fetching cart count from API:', error);
        cartCountBadge.textContent = '0';
        cartCountBadge.style.display = 'none';
    }
}


window.clearCart = clearCart;
window.updateCartCount = updateCartCountFromServer; 
document.addEventListener('DOMContentLoaded', window.updateCartCount);