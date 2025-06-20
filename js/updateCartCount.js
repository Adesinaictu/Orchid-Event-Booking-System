// js/updateCartCount.js

// Make the function globally accessible
window.updateCartCount = async function() {
    const cartCountBadge = document.getElementById('cart-count-badge');
    const API_URL = 'api/get_cart_count.php';

    try {
        const response = await fetch(API_URL);

        if (!response.ok) {
            const errorDetails = await response.text();
            throw new Error(`HTTP error! status: ${response.status}, details: ${errorDetails}`);
        }

        const data = await response.json();
        if (data.success) {
            cartCountBadge.textContent = data.count;
        } else {
            console.error('Error in API response for cart count:', data.message);
            cartCountBadge.textContent = '0';
        }
    } catch (error) {
        console.error('Error fetching cart count:', error);
        cartCountBadge.textContent = '0';
    }
}

// Call it on page load
document.addEventListener('DOMContentLoaded', updateCartCount);