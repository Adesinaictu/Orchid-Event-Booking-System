document.addEventListener('DOMContentLoaded', function() {
    // --- DOM Elements ---
    const attendeeDetailsTabLink = document.getElementById('details-tab');
    const paymentInfoTabLink = document.getElementById('payment-tab');
    const orderReviewTabLink = document.getElementById('review-tab');

    const nextToPaymentBtn = document.getElementById('next-to-payment');
    // Renamed for clarity:
    const prevFromPaymentToDetailsBtn = document.getElementById('prev-to-details'); // Button on Payment tab to go to Details
    const nextToReviewBtn = document.getElementById('next-to-review');
    const prevFromReviewToPaymentBtn = document.getElementById('prev-to-payment'); // Button on Review tab to go to Payment
    const confirmBookingBtn = document.getElementById('confirm-booking-btn');

    const attendeeDetailsForm = document.getElementById('attendee-details-form');
    const paymentForm = document.getElementById('payment-form');
    const ticketHolderFormsContainer = document.getElementById('ticket-holder-forms');

    // Review Tab elements
    const reviewAttendeeName = document.getElementById('review-attendee-name');
    const reviewAttendeeContact = document.getElementById('review-attendee-contact');
    const reviewTicketsList = document.getElementById('review-tickets-list');
    const reviewTotalPrice = document.getElementById('review-total-price');

    // Modals related elements
    const bookingIdDisplay = document.getElementById('booking-id-display');
    const bookingSuccessModalElement = document.getElementById('bookingSuccessModal'); // Get the modal element itself

    // --- Global Data Stores ---
    let cartItems = []; // Stores items from the cart
    let attendeeDetails = {}; // Stores main attendee details
    let ticketHoldersDetails = []; // Stores details for each ticket holder
    let paymentInfo = {}; // Stores payment details

    // --- API Endpoints ---
    const GET_CART_API_URL = 'http://localhost/orchid/api/get_cart.php'; // API to fetch cart items
    const PLACE_ORDER_API_URL = 'http://localhost/orchid/api/place_order.php'; // API to place the order


    // --- Helper to navigate tabs ---
    function navigateToTab(tabElement) {
        const tab = new bootstrap.Tab(tabElement);
        tab.show();
    }

    // --- Function to fetch cart items ---
    async function fetchCartItems() {
        try {
            const response = await fetch(GET_CART_API_URL);
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP error! status: ${response.status}. Details: ${errorText}`);
            }
            const data = await response.json();

            if (data.success) {
                cartItems = data.data.cart;

                if (!Array.isArray(cartItems)) {
                    console.error('Expected cartItems to be an array, but received:', cartItems);
                    throw new Error('Invalid data format received for cart items.');
                }

                if (cartItems.length === 0) {
                    alert('Your cart is empty. Please add items to your cart before checking out.');
                    // window.location.href = 'index.html'; // Uncomment if you want redirection
                } else {
                    renderTicketHolderForms();
                }
            } else {
                alert('Failed to load cart items: ' + (data.message || 'Unknown error.'));
                // window.location.href = 'cart.html'; // Uncomment if you want redirection
            }
        } catch (error) {
            console.error('Error fetching cart items:', error);
            alert('An error occurred while loading your cart. Please try again or go back to cart.');
            // window.location.href = 'cart.html'; // Uncomment if you want redirection
        }
    }

    function renderTicketHolderForms() {
        ticketHolderFormsContainer.innerHTML = ''; // Clear previous forms

        let totalTickets = 0;
        cartItems.forEach(item => {
            totalTickets += item.quantity;
        });

        if (totalTickets > 1) { // Only show individual forms if more than 1 ticket
            // Add a general instruction if multiple tickets are present
            const instructionDiv = document.createElement('div');
            instructionDiv.className = 'alert alert-info small mt-3';
            instructionDiv.textContent = 'Please provide details for each ticket holder below. Leave blank if the main attendee is the only ticket holder or tickets are for the same person.';
            ticketHolderFormsContainer.appendChild(instructionDiv);
        }


        cartItems.forEach((item, itemIndex) => {
            for (let i = 0; i < item.quantity; i++) {
                const formGroup = document.createElement('div');
                formGroup.className = 'card p-3 mb-3 bg-light';
                formGroup.innerHTML = `
                    <h6 class="mb-3">Ticket ${i + 1} for "${item.name}"</h6>
                    <div class="row g-2">
                        <div class="col-md-6 mb-2">
                            <label for="ticketHolderFirstName-${itemIndex}-${i}" class="form-label small">First Name</label>
                            <input type="text" class="form-control form-control-sm ticket-holder-first-name"
                                id="ticketHolderFirstName-${itemIndex}-${i}" data-item-index="${itemIndex}" data-ticket-index="${i}">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label for="ticketHolderLastName-${itemIndex}-${i}" class="form-label small">Last Name</label>
                            <input type="text" class="form-control form-control-sm ticket-holder-last-name"
                                id="ticketHolderLastName-${itemIndex}-${i}" data-item-index="${itemIndex}" data-ticket-index="${i}">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label for="ticketHolderEmail-${itemIndex}-${i}" class="form-label small">Email</label>
                            <input type="email" class="form-control form-control-sm ticket-holder-email"
                                id="ticketHolderEmail-${itemIndex}-${i}" data-item-index="${itemIndex}" data-ticket-index="${i}">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label for="ticketHolderPhone-${itemIndex}-${i}" class="form-label small">Phone (Optional)</label>
                            <input type="tel" class="form-control form-control-sm ticket-holder-phone"
                                id="ticketHolderPhone-${itemIndex}-${i}" data-item-index="${itemIndex}" data-ticket-index="${i}">
                        </div>
                    </div>
                `;
                ticketHolderFormsContainer.appendChild(formGroup);
            }
        });
    }

    // --- Validate Attendee Details and collect data ---
    function validateAndCollectAttendeeDetails() {
        // First, check the main attendee form
        if (!attendeeDetailsForm.checkValidity()) {
            attendeeDetailsForm.classList.add('was-validated');
            return false;
        }

        attendeeDetails = {
            firstName: document.getElementById('firstName').value,
            lastName: document.getElementById('lastName').value,
            email: document.getElementById('email').value,
            phone: document.getElementById('phone').value,
            address: document.getElementById('address').value
        };

        ticketHoldersDetails = [];
        const ticketHolderFirstNameInputs = document.querySelectorAll('.ticket-holder-first-name');
        const totalTicketsInCart = cartItems.reduce((sum, item) => sum + item.quantity, 0);

        if (ticketHolderFirstNameInputs.length > 0 && totalTicketsInCart > 1) {
            let allTicketHoldersValid = true;
            ticketHolderFirstNameInputs.forEach(input => {
                const itemIndex = parseInt(input.dataset.itemIndex);
                const ticketIndex = parseInt(input.dataset.ticketIndex);

                const fNameInput = document.getElementById(`ticketHolderFirstName-${itemIndex}-${ticketIndex}`);
                const lNameInput = document.getElementById(`ticketHolderLastName-${itemIndex}-${ticketIndex}`);
                const emailInput = document.getElementById(`ticketHolderEmail-${itemIndex}-${ticketIndex}`);
                const phoneInput = document.getElementById(`ticketHolderPhone-${itemIndex}-${ticketIndex}`);

                const fName = fNameInput.value.trim();
                const lName = lNameInput.value.trim();
                const email = emailInput.value.trim();
                const phone = phoneInput.value.trim();

                // If any field is partially filled, all required fields for that ticket must be filled
                if (fName || lName || email || phone) {
                    if (!fName || !lName || !email) {
                        allTicketHoldersValid = false;
                        // Trigger HTML5 validation messages for specific inputs
                        if (!fName) fNameInput.reportValidity();
                        if (!lName) lNameInput.reportValidity();
                        if (!email) emailInput.reportValidity();
                        return; // Skip to next iteration
                    }
                }

                ticketHoldersDetails.push({
                    event_id: cartItems[itemIndex].id, // Associate ticket holder with specific event
                    event_name: cartItems[itemIndex].name,
                    first_name: fName || attendeeDetails.firstName, // Use main attendee if not provided
                    last_name: lName || attendeeDetails.lastName,
                    email: email || attendeeDetails.email,
                    phone: phone || attendeeDetails.phone,
                    ticket_price: cartItems[itemIndex].price // Store price for individual tickets too
                });
            });

            if (!allTicketHoldersValid) {
                alert('Please ensure all required fields for individual ticket holders are filled, or leave them completely blank to use main attendee details.');
                return false;
            }
        } else {
            // If no specific ticket holders forms (only one ticket) or they are left blank, assume main attendee is the ticket holder for all
            cartItems.forEach(item => {
                for (let i = 0; i < item.quantity; i++) {
                    ticketHoldersDetails.push({
                        event_id: item.id,
                        event_name: item.name,
                        first_name: attendeeDetails.firstName,
                        last_name: attendeeDetails.lastName,
                        email: attendeeDetails.email,
                        phone: attendeeDetails.phone,
                        ticket_price: item.price
                    });
                }
            });
        }
        return true;
    }

    // --- Validate Payment Info and collect data ---
    function validateAndCollectPaymentInfo() {
        if (!paymentForm.checkValidity()) {
            paymentForm.classList.add('was-validated');
            return false;
        }

        paymentInfo = {
            cardName: document.getElementById('cardName').value,
            cardNumber: document.getElementById('cardNumber').value,
            expDate: document.getElementById('expDate').value,
            cvv: document.getElementById('cvv').value
        };
        return true;
    }

    // --- Populate Review Tab ---
    function populateReviewTab() {
        reviewAttendeeName.textContent = `${attendeeDetails.firstName} ${attendeeDetails.lastName}`;
        reviewAttendeeContact.textContent = `${attendeeDetails.email} | ${attendeeDetails.phone}`;

        reviewTicketsList.innerHTML = '';
        let totalOrderPrice = 0;

        cartItems.forEach(item => {
            const listItem = document.createElement('li');
            listItem.className = 'list-group-item d-flex justify-content-between align-items-center';
            const itemPrice = parseFloat(item.price);
            const subtotal = itemPrice * item.quantity;
            totalOrderPrice += subtotal;

            listItem.innerHTML = `
                <div>
                    <strong>${item.name}</strong>
                    <br><small class="text-muted">${item.quantity} x NGN ${itemPrice.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</small>
                </div>
                <span>NGN ${subtotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
            `;
            reviewTicketsList.appendChild(listItem);
        });

        reviewTotalPrice.textContent = `NGN ${totalOrderPrice.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }

    // --- Event Listeners for Navigation Buttons ---
    if (nextToPaymentBtn) {
        nextToPaymentBtn.addEventListener('click', function() {
            if (validateAndCollectAttendeeDetails()) {
                navigateToTab(paymentInfoTabLink); // Use the correct tab link variable
            }
        });
    }

    if (prevFromPaymentToDetailsBtn) { // Corrected variable name
        prevFromPaymentToDetailsBtn.addEventListener('click', function() {
            navigateToTab(attendeeDetailsTabLink); // Use the correct tab link variable
        });
    }

    if (nextToReviewBtn) {
        nextToReviewBtn.addEventListener('click', function() {
            if (validateAndCollectPaymentInfo()) {
                populateReviewTab();
                navigateToTab(orderReviewTabLink); // Use the correct tab link variable
            }
        });
    }

    if (prevFromReviewToPaymentBtn) { // Corrected variable name
        prevFromReviewToPaymentBtn.addEventListener('click', function() {
            navigateToTab(paymentInfoTabLink); // Use the correct tab link variable
        });
    }

    // --- Confirm Booking Button Handler ---
    if (confirmBookingBtn) {
        confirmBookingBtn.addEventListener('click', async function() {
            confirmBookingBtn.disabled = true;
            confirmBookingBtn.textContent = 'Processing...';

            try {
                const response = await fetch(PLACE_ORDER_API_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        attendeeDetails: attendeeDetails,
                        ticketHolders: ticketHoldersDetails, // Send individual ticket holder details
                        paymentInfo: paymentInfo,
                        cartItems: cartItems // Send cart items for backend verification/processing
                    })
                });

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({ message: 'No detailed message from server.' }));
                    throw new Error(`HTTP error! status: ${response.status} - ${errorData.message}`);
                }

                const result = await response.json();

                if (result.success) {
                    // Update the booking ID in the modal
                    bookingIdDisplay.textContent = result.data.bookingId || '#N/A'; // Access data.bookingId

                    const bookingSuccessModalInstance = new bootstrap.Modal(bookingSuccessModalElement); // Create new instance
                    bookingSuccessModalInstance.show();

                    // Clear the cart after successful booking using the globally available function
                    if (typeof window.clearCart === 'function') {
                        window.clearCart();
                    } else {
                        console.warn('window.clearCart function not found. Cart might not be cleared on frontend.');
                        localStorage.removeItem('cart'); // Fallback if for some reason window.clearCart is not available
                    }

                } else {
                    alert('Booking failed: ' + (result.message || 'Unknown error.'));
                    console.error('Backend booking error:', result.message);
                }
            } catch (error) {
                console.error('Client-side booking error:', error);
                alert('An error occurred during booking. Please try again. Details: ' + error.message);
            } finally {
                confirmBookingBtn.disabled = false;
                confirmBookingBtn.textContent = 'Confirm Booking';
            }
        });
    } else {
        console.warn('Confirm Booking button (id="confirm-booking-btn") not found.');
    }

    // --- Initial Load ---
    fetchCartItems(); // Load cart items when the page loads

    // Make sure updateCartCount is called using the globally available function
    if (typeof window.updateCartCount === 'function') {
        window.updateCartCount(); // This will now call the server-fetching version
    } else {
        console.warn('window.updateCartCount function not found. Cart badge might not be updated on load.');
    }
});