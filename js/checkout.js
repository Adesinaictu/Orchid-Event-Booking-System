document.addEventListener('DOMContentLoaded', function() {
    // --- DOM Elements ---
    const attendeeDetailsTab = document.getElementById('details-tab');
    const paymentInfoTab = document.getElementById('payment-tab');
    const orderReviewTab = document.getElementById('review-tab');

    const nextToPaymentBtn = document.getElementById('next-to-payment');
    const prevToDetailsBtn = document.getElementById('prev-to-details');
    const nextToReviewBtn = document.getElementById('next-to-review');
    const prevToPaymentBtn = document.getElementById('prev-to-payment');
    const confirmBookingBtn = document.getElementById('confirm-booking-btn');

    const attendeeDetailsForm = document.getElementById('attendee-details-form');
    const paymentForm = document.getElementById('payment-form');
    const ticketHolderFormsContainer = document.getElementById('ticket-holder-forms');

    // Review Tab elements
    const reviewAttendeeName = document.getElementById('review-attendee-name');
    const reviewAttendeeContact = document.getElementById('review-attendee-contact');
    const reviewTicketsList = document.getElementById('review-tickets-list');
    const reviewTotalPrice = document.getElementById('review-total-price');

    // Modals related elements (bookingIdDisplay is kept here as it's a direct DOM element)
    const bookingIdDisplay = document.getElementById('booking-id-display');

    // --- Global Data Stores ---
    let cartItems = []; // Stores items from the cart
    let attendeeDetails = {}; // Stores main attendee details
    let ticketHoldersDetails = []; // Stores details for each ticket holder
    let paymentInfo = {}; // Stores payment details

    // --- API Endpoints ---
    const GET_CART_API_URL = 'http://localhost/orchid/backend/get_cart.php'; // API to fetch cart items
    const PLACE_ORDER_API_URL = 'http://localhost/orchid/backend/place_order.php'; // API to place the order


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
                cartItems = data.cart;
                if (cartItems.length === 0) {
                    alert('Your cart is empty. Please add items to your cart before checking out.');
                    window.location.href = 'index.html'; // Redirect to events page
                } else {
                    renderTicketHolderForms();
                    updateCartCount(); // Update the navbar cart count
                }
            } else {
                alert('Failed to load cart items: ' + (data.message || 'Unknown error.'));
                window.location.href = 'cart.html'; // Redirect back to cart
            }
        } catch (error) {
            console.error('Error fetching cart items:', error);
            alert('An error occurred while loading your cart. Please try again or go back to cart.');
            window.location.href = 'cart.html'; // Redirect back to cart
        }
    }

    // --- Function to render dynamic ticket holder forms ---
    function renderTicketHolderForms() {
        ticketHolderFormsContainer.innerHTML = ''; // Clear previous forms

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
                                id="ticketHolderFirstName-${itemIndex}-${i}" data-item-index="${itemIndex}" data-ticket-index="${i}" required>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label for="ticketHolderLastName-${itemIndex}-${i}" class="form-label small">Last Name</label>
                            <input type="text" class="form-control form-control-sm ticket-holder-last-name"
                                id="ticketHolderLastName-${itemIndex}-${i}" data-item-index="${itemIndex}" data-ticket-index="${i}" required>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label for="ticketHolderEmail-${itemIndex}-${i}" class="form-label small">Email</label>
                            <input type="email" class="form-control form-control-sm ticket-holder-email"
                                id="ticketHolderEmail-${itemIndex}-${i}" data-item-index="${itemIndex}" data-ticket-index="${i}" required>
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

        if (ticketHolderFirstNameInputs.length > 0) {
            let allTicketHoldersValid = true;
            ticketHolderFirstNameInputs.forEach(input => {
                const itemIndex = parseInt(input.dataset.itemIndex);
                const ticketIndex = parseInt(input.dataset.ticketIndex);

                const fName = document.getElementById(`ticketHolderFirstName-${itemIndex}-${ticketIndex}`).value;
                const lName = document.getElementById(`ticketHolderLastName-${itemIndex}-${ticketIndex}`).value;
                const email = document.getElementById(`ticketHolderEmail-${itemIndex}-${ticketIndex}`).value;
                const phone = document.getElementById(`ticketHolderPhone-${itemIndex}-${ticketIndex}`).value;

                if (!fName || !lName || !email) {
                    allTicketHoldersValid = false;
                    input.reportValidity(); // Show validation message for the invalid input
                }

                ticketHoldersDetails.push({
                    event_id: cartItems[itemIndex].id, // Associate ticket holder with specific event
                    event_name: cartItems[itemIndex].name,
                    first_name: fName,
                    last_name: lName,
                    email: email,
                    phone: phone,
                    ticket_price: cartItems[itemIndex].price // Store price for individual tickets too
                });
            });

            if (!allTicketHoldersValid) {
                alert('Please fill in all required fields for all ticket holders.');
                return false;
            }
        } else {
            // If no specific ticket holders forms, assume main attendee is the ticket holder for all
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
                navigateToTab(paymentInfoTab);
            }
        });
    }

    if (prevToDetailsBtn) {
        prevToDetailsBtn.addEventListener('click', function() {
            navigateToTab(attendeeDetailsTab);
        });
    }

    if (nextToReviewBtn) {
        nextToReviewBtn.addEventListener('click', function() {
            if (validateAndCollectPaymentInfo()) {
                populateReviewTab();
                navigateToTab(orderReviewTab);
            }
        });
    }

    if (prevToPaymentBtn) {
        prevToPaymentBtn.addEventListener('click', function() {
            navigateToTab(paymentInfoTab);
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
                    bookingIdDisplay.textContent = result.bookingId || '#N/A';

                    // --- REVISED MODAL INITIALIZATION AND DISPLAY ---
                    const modalElement = document.getElementById('bookingSuccessModal'); // Ensure this ID matches your HTML modal
                    if (modalElement) {
                        const bookingSuccessModalInstance = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
                        bookingSuccessModalInstance.show();
                    } else {
                        console.error('Booking Success Modal element with ID "bookingSuccessModal" not found!');
                        alert('Booking successful, but confirmation modal could not be displayed.');
                    }
                    // --- END REVISED MODAL HANDLING ---


                    // Clear the cart after successful booking
                    // This assumes the backend also clears the cart for the user
                    // or provides an endpoint to do so. For frontend only:
                    if (typeof clearCart === 'function') { // Assuming clearCart is defined in updateCartCount.js
                        clearCart();
                    } else {
                        console.warn('clearCart function not found. Cart might not be cleared on frontend.');
                        localStorage.removeItem('cart'); // Fallback if clearCart not available
                        updateCartCount(); // Update badge
                    }

                    // Optionally redirect after a short delay or user closes modal
                    // setTimeout(() => {
                    //      window.location.href = `booking-success.html?bookingId=${result.bookingId}`;
                    // }, 3000); // Redirect after 3 seconds
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
    updateCartCount(); // Ensure navbar cart count is accurate

    

});