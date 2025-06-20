document.addEventListener('DOMContentLoaded', function() {
    // --- Cart Count Update (similar to other pages) ---
    // Ensure the cart count badge is updated when the contact page loads.
    // This assumes updateCartCount.js is loaded and defines a global function.
    if (typeof updateCartCount === 'function') {
        updateCartCount();
    } else {
        console.warn('updateCartCount function not found. Cart badge might not update on contact page.');
    }

    // --- Contact Form Submission Handling ---
    const contactForm = document.querySelector('.contact-content form');

    if (contactForm) {
        contactForm.addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent the default form submission

            const fullName = document.getElementById('fullName').value;
            const emailAddress = document.getElementById('emailAddress').value;
            const subject = document.getElementById('subject').value;
            const message = document.getElementById('message').value;

            // Basic validation
            if (!fullName || !emailAddress || !message) {
                alert('Please fill in all required fields (Full Name, Email Address, and Your Message).');
                return;
            }

            // In a real application, you would send this data to a backend server
            // using fetch() or XMLHttpRequest.
            console.log('Contact Form Submitted:');
            console.log('Full Name:', fullName);
            console.log('Email Address:', emailAddress);
            console.log('Subject:', subject);
            console.log('Message:', message);

            // Simulate a successful submission
            alert('Thank you for your message! We will get back to you shortly.');

            // Clear the form after successful submission
            contactForm.reset();

            // Example of how you might send data to a backend (uncomment and modify for actual use)
            /*
            fetch('http://localhost/orchid/backend/submit_contact_form.php', { // Replace with your actual backend endpoint
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    fullName: fullName,
                    emailAddress: emailAddress,
                    subject: subject,
                    message: message
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert('Thank you for your message! We will get back to you shortly.');
                    contactForm.reset(); // Clear the form
                } else {
                    alert('Failed to send message: ' + (data.message || 'Unknown error.'));
                }
            })
            .catch(error => {
                console.error('Error submitting contact form:', error);
                alert('An error occurred while sending your message. Please try again later.');
            });
            */
        });
    } else {
        console.warn('Contact form not found on this page.');
    }
});