document.addEventListener('DOMContentLoaded', function() {
    const API_BASE_URL = 'http://localhost/orchid/api/admin_dashboard_api.php'; 

    async function fetchDashboardData() {
        try {
            const response = await fetch(API_BASE_URL);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();

            if (data.success) {
                updateDashboardUI(data.data);
            } else {
                console.error('API Error:', data.message);
                alert('Failed to load dashboard data: ' + data.message);
            }
        } catch (error) {
            console.error('Fetch Error:', error);
            alert('An error occurred while fetching dashboard data. Please check console for details.');
        }
    }

    function updateDashboardUI(data) {
        // Update Summary Cards
        document.querySelector('.card.bg-primary .card-title').textContent = data.totalEvents;
        document.querySelector('.card.bg-success .card-title').textContent = data.totalBookings;
        document.querySelector('.card.bg-warning .card-title').textContent = `₦${parseInt(data.totalRevenue).toLocaleString()}+`;

        // Update Recent Bookings Table
        const recentBookingsTableBody = document.querySelector('.card-body .table tbody');
        recentBookingsTableBody.innerHTML = ''; // Clear existing dummy data

        if (data.recentBookings.length > 0) {
            data.recentBookings.forEach(booking => {
                const row = `
                    <tr>
                        <td>#BKG${booking.booking_id}</td>
                        <td>${booking.event_name}</td>
                        <td>${booking.user_name}</td>
                        <td>${booking.quantity}</td>
                        <td>₦${parseFloat(booking.total_amount).toLocaleString()}</td>
                        <td>${new Date(booking.booking_date).toLocaleDateString()}</td>
                    </tr>
                `;
                recentBookingsTableBody.insertAdjacentHTML('beforeend', row);
            });
        } else {
            recentBookingsTableBody.innerHTML = '<tr><td colspan="6" class="text-center">No recent bookings.</td></tr>';
        }


        // Update Upcoming Events List
        const upcomingEventsList = document.querySelector('.card.shadow-sm .list-group');
        upcomingEventsList.innerHTML = ''; // Clear existing dummy data

        if (data.upcomingEvents.length > 0) {
            data.upcomingEvents.forEach(event => {
                const eventDate = new Date(event.date);
                const options = { month: 'long', day: 'numeric', year: 'numeric' };
                const formattedDate = eventDate.toLocaleDateString('en-US', options);

                const listItem = `
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        ${event.name}
                        <span class="badge bg-info">${formattedDate}</span>
                    </li>
                `;
                upcomingEventsList.insertAdjacentHTML('beforeend', listItem);
            });
        } else {
            upcomingEventsList.innerHTML = '<li class="list-group-item text-center">No upcoming events.</li>';
        }
    }

    // Call the function to fetch data when the page loads
    fetchDashboardData();
});