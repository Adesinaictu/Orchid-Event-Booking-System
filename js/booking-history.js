document.addEventListener('DOMContentLoaded', () => {
    // --- DOM Element References ---
    const bookingListContainer = document.getElementById('booking-list-container');
    const upcomingBookingsTab = document.getElementById('upcoming-bookings-tab');
    const pastBookingsTab = document.getElementById('past-bookings-tab');
    const noBookingsMessage = document.getElementById('no-bookings-message');

    // QR Code Modal elements
    const qrCodeModal = new bootstrap.Modal(document.getElementById('qrCodeModal'));
    const qrCodeImage = document.getElementById('qr-code-image');
    const qrBookingId = document.getElementById('qr-booking-id'); // This will now show booking_item_id
    const qrEventName = document.getElementById('qr-event-name');
    const qrTicketType = document.getElementById('qr-ticket-type');
    const downloadQrBtn = document.getElementById('download-qr-btn');

    // --- API Endpoint ---
    const BOOKINGS_API_URL = 'http://localhost/orchid/api/bookings.php';
    const TICKET_DOWNLOAD_API_URL = 'http://localhost/orchid/api/download_ticket.php';

    // --- Utility Functions ---
    function showLoading() {
        if (bookingListContainer) {
            bookingListContainer.innerHTML = `
                <div class="text-center py-5 col-12">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading bookings...</span>
                    </div>
                    <p class="mt-2 text-muted">Fetching your booking history...</p>
                </div>
            `;
        }
        if (noBookingsMessage) noBookingsMessage.style.display = 'none';
    }

    function showErrorMessage(message) {
        if (bookingListContainer) {
            bookingListContainer.innerHTML = `
                <div class="text-center py-5 col-12">
                    <p class="text-danger fw-bold">Error: ${message}</p>
                    <p class="text-muted">Failed to load bookings. Please try again later.</p>
                </div>
            `;
        }
        if (noBookingsMessage) noBookingsMessage.style.display = 'none';
    }

    function showNoBookingsMessage(type) {
        if (bookingListContainer) {
            bookingListContainer.innerHTML = ''; // Clear any loading/error messages
        }
        if (noBookingsMessage) {
            noBookingsMessage.textContent = `No ${type} bookings found.`;
            noBookingsMessage.style.display = 'block';
        }
    }

    // --- Function to Fetch and Display Bookings ---
    async function fetchBookings(type = 'upcoming') {
        if (!bookingListContainer) {
            console.error("Booking list container (ID 'booking-list-container') not found.");
            return;
        }

        showLoading();

        try {
            const response = await fetch(`${BOOKINGS_API_URL}?type=${type}`);
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP error! Status: ${response.status} - ${errorText}`);
            }
            const data = await response.json();

            if (data.success && Array.isArray(data.bookings)) {
                displayBookings(data.bookings, type);
            } else {
                console.error('API response format incorrect:', data);
                showErrorMessage('Invalid data received from server.');
            }
        } catch (error) {
            console.error('Error fetching bookings:', error);
            showErrorMessage(`Failed to load bookings: ${error.message}`);
        }
    }

    // --- Function to Display Bookings ---
    function displayBookings(bookings, type) {
        if (!bookingListContainer) return;

        bookingListContainer.innerHTML = ''; // Clear previous content

        if (bookings.length === 0) {
            showNoBookingsMessage(type);
            return;
        } else {
            if (noBookingsMessage) noBookingsMessage.style.display = 'none';
        }

        bookings.forEach(booking => {
            const formattedPrice = `₦${parseFloat(booking.total_price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            const bookingStatusClass = booking.status === 'confirmed' ? 'badge bg-success' : 'badge bg-warning text-dark';
            // Show QR button for upcoming or confirmed past bookings
            const showQrButton = (type === 'upcoming' && booking.status !== 'cancelled') || (type === 'past' && booking.status === 'completed');

            // Construct QR data string dynamically based on available info
            // Ensure these data points are available in the `bookings` object returned by bookings.php
            const qrDataContent = `TICKET_ID:${booking.booking_id}|EVENT_ID:${booking.event_id}|USER_ID:1|QUANTITY:${booking.tickets_bought}|STATUS:${booking.status}`;


            const bookingCardHtml = `
                <div class="card mb-3 shadow-sm booking-card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5 class="card-title">${booking.event_name}</h5>
                                <p class="card-text text-muted mb-1">
                                    <i class="far fa-calendar-alt me-1"></i> ${new Date(booking.event_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })} at ${booking.event_time}
                                </p>
                                <p class="card-text text-muted mb-1">
                                    <i class="fas fa-map-marker-alt me-1"></i> ${booking.event_location}
                                </p>
                                <p class="card-text mb-1">
                                    <strong>Ticket ID:</strong> #${booking.booking_id}
                                </p>
                                <p class="card-text mb-1">
                                    <strong>Tickets:</strong> ${booking.tickets_bought} ${booking.ticket_type ? `(${booking.ticket_type})` : ''}
                                </p>
                                <p class="card-text mb-2">
                                    <strong>Total Paid (for this item):</strong> ${formattedPrice}
                                </p>
                                <span class="${bookingStatusClass}">${booking.status.toUpperCase()}</span>
                            </div>
                            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                <a href="event-detail.html?id=${booking.event_id}" class="btn btn-outline-primary btn-sm mb-2 w-100">View Event</a>
                                ${showQrButton ? `
                                <button class="btn btn-info btn-sm mb-2 w-100 view-qr-btn"
                                    data-booking-item-id="${booking.booking_id}"
                                    data-event-name="${booking.event_name}"
                                    data-ticket-type="${booking.ticket_type || 'N/A'}"
                                    data-qr-data="${qrDataContent}">
                                    <i class="fas fa-qrcode me-1"></i> View QR Code
                                </button>
                                ` : ''}
                                <button class="btn btn-secondary btn-sm w-100 download-ticket-btn"
                                    data-booking-item-id="${booking.booking_id}"
                                    data-event-name="${booking.event_name}">
                                    <i class="fas fa-download me-1"></i> Download Ticket
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            bookingListContainer.insertAdjacentHTML('beforeend', bookingCardHtml);
        });

        // Attach event listeners for QR and Download buttons after rendering
        setupBookingCardListeners();
    }

    // --- Setup Listeners for Dynamically Added Booking Cards ---
    function setupBookingCardListeners() {
        document.querySelectorAll('.view-qr-btn').forEach(button => {
            button.removeEventListener('click', handleViewQrClick); // Prevent duplicate listeners
            button.addEventListener('click', handleViewQrClick);
        });

        document.querySelectorAll('.download-ticket-btn').forEach(button => {
            button.removeEventListener('click', handleDownloadTicketClick); // Prevent duplicate listeners
            button.addEventListener('click', handleDownloadTicketClick);
        });
    }

    // --- Event Handler for View QR Code Button ---
    function handleViewQrClick(event) {
        const button = event.currentTarget;
        const bookingItemId = button.dataset.bookingItemId; // Use booking_item_id
        const eventName = button.dataset.eventName;
        const ticketType = button.dataset.ticketType;
        const qrData = button.dataset.qrData; // Dynamically constructed QR data

        if (qrCodeImage) {
            qrCodeImage.src = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(qrData)}`;
            qrCodeImage.alt = `QR Code for Ticket #${bookingItemId}`;
        }

        if (qrBookingId) qrBookingId.textContent = `#${bookingItemId}`;
        if (qrEventName) qrEventName.textContent = eventName;
        if (qrTicketType) qrTicketType.textContent = ticketType;

        qrCodeModal.show(); // Show the Bootstrap modal
    }

    // --- Event Handler for Download QR Button (inside modal) ---
    if (downloadQrBtn) {
        downloadQrBtn.addEventListener('click', () => {
            if (qrCodeImage && qrCodeImage.src) {
                const imageUrl = qrCodeImage.src;
                const link = document.createElement('a');
                link.href = imageUrl;
                link.download = `QR_Ticket_#${qrBookingId.textContent.replace('#', '')}.png`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } else {
                alert('QR Code image not available for download.');
            }
        });
    }

    // --- Event Handler for Download Ticket Button (Updated for Server-side PDF) ---
    function handleDownloadTicketClick(event) {
        const bookingItemId = event.currentTarget.dataset.bookingItemId; // Now use booking_item_id
        const eventName = event.currentTarget.dataset.eventName;

        if (confirm(`Do you want to download the ticket for Booking Item ID: #${bookingItemId} (${eventName})?`)) {
            // Construct the URL to your PHP PDF generation endpoint
            // Pass booking_item_id as 'booking_id' parameter for consistency with previous PHP
            const downloadUrl = `${TICKET_DOWNLOAD_API_URL}?booking_id=${bookingItemId}`;

            // Open in a new tab/window to trigger the server-side PDF download
            window.open(downloadUrl, '_blank');
        }
    }

    // --- Tab Switching Logic ---
    if (upcomingBookingsTab) {
        upcomingBookingsTab.addEventListener('click', (e) => {
            e.preventDefault();
            upcomingBookingsTab.classList.add('active');
            pastBookingsTab.classList.remove('active');
            fetchBookings('upcoming');
        });
    }

    if (pastBookingsTab) {
        pastBookingsTab.addEventListener('click', (e) => {
            e.preventDefault();
            pastBookingsTab.classList.add('active');
            upcomingBookingsTab.classList.remove('active');
            fetchBookings('past');
        });
    }

    // --- Initial Load ---
    // Fetch upcoming bookings when the page first loads
    fetchBookings('upcoming');

    // Update cart count badge on page load (assuming updateCartCount is globally available)
    if (typeof updateCartCount === 'function') {
        updateCartCount();
    } else {
        console.warn("updateCartCount function not found. Cart badge may not update on page load.");
    }
});