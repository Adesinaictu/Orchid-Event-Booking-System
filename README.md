Orchid Event Booking System
Project Overview
This project is a fully functional Online Event Booking System designed to simplify the process of Browse, searching for, and booking tickets for various events. Whether you're looking for concerts, workshops, conferences, or local gatherings, this system aims to provide a seamless and intuitive experience for users.

It's built with a strong focus on usability and responsiveness, ensuring a consistent experience across different devices.

Features
Here's what our system offers:

User Authentication: Secure registration, login, and logout functionalities for users.
Event Browse: Easily view a comprehensive list of available events.
Event Search & Filter: Quickly find events by title, date, location, or category.
Event Details: Access detailed information for each event, including description, date, time, location, and available tickets.
Ticket Booking: Intuitive process for users to select the number of tickets and book their spot.
Booking History: Users can view their past and upcoming bookings.
Responsive Design: Optimized for a smooth experience on desktops, tablets, and mobile devices thanks to Bootstrap.
Technologies Used
The system leverages a classic and robust web development stack:

Frontend:
HTML5: For structuring the web content.
CSS3: For styling and visual presentation.
Bootstrap 5: A powerful framework for responsive design and pre-built UI components.
JavaScript : For client-side interactivity, dynamic content updates, and form validation.
Backend:
PHP : Server-side scripting language handling business logic, user authentication, and database interactions.
Database:
MySQL: A relational database management system for storing all event, user, and booking data.
Web Server:
Apache: The web server responsible for serving the application.
Getting Started
Follow these steps to get a copy of the project up and running on your local machine.

Prerequisites
You'll need a local web server environment that supports PHP and MySQL. We recommend using a package like XAMPP.

XAMPP: Download XAMPP
WAMP Server: Download WAMP Server
A modern web browser (e.g., Chrome, Firefox, Edge).
A code editor (e.g., VS Code, Sublime Text).
Installation Steps
Clone the Repository (or Download):

Bash

git clone https://github.com/your-username/orichid.git
(Replace your-username/online-event-booking-system.git with your actual repository URL if you've hosted it.)

Place Project in Web Server Root:

Navigate to your web server's document root:
XAMPP: C:\xampp\htdocs\
WAMP: C:\wamp\www\ (or C:\wamp64\www\)
Move the cloned orchid folder into this directory.
Start Apache and MySQL:

Open your XAMPP/WAMP/MAMP control panel.
Start the Apache and MySQL services. Ensure they are running (usually indicated by a green status).
Database Setup:

Open your web browser and go to http://localhost/phpmyadmin/.
Click on the "New" button in the left sidebar to create a new database.
Name the database event_booking_db (or a name of your choice, but remember to update config/database.php if you change it).
Select the newly created database from the left sidebar.
Go to the "Import" tab.
Click "Choose File" and select the database.sql file located in your project's root directory.
Click "Go" to import the database schema and initial data.
Configure Database Connection:

Open the config/database.php file in your code editor.
Update the database connection details if they differ from the defaults:
PHP

<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Default for XAMPP
define('DB_PASSWORD', ''); // Default for XAMPP
define('DB_NAME', 'Orchidfy_db'); // Must match the database name you created
?>
Access the Application:

Open your web browser and navigate to: http://localhost/orchid/
You should now see the home page of the Online Event Booking System!

Project Structure
oechid/
├── assets/
│   ├── css/          # Custom CSS files (e.g., style.css)
│   ├── js/           # Custom JavaScript files (e.g., script.js)
│   └── img/          # Event images, logos, etc.
├── config/           # Configuration files (e.g., database.php)
├── includes/         # Reusable PHP components (header.php, footer.php, functions.php)
├── classes/          # PHP classes for object-oriented structure (User.php, Event.php, Booking.php)
├── views/            # PHP files responsible for rendering HTML (logout.php, get_event_details.php, etc.)
├── admin/            #  Admin panel files
├── database.sql      # SQL script for database schema and initial data
├── index.html         # Main entry point for the application
├── .htaccess         # Apache rewrite rules (if implemented for clean URLs)
└── README.md         # This file
Contributing
We welcome contributions! If you'd like to contribute, please follow these steps:

Fork the repository.
Create a new branch (git checkout -b feature/your-feature-name).
Make your changes and ensure they adhere to the project's coding standards.
Commit your changes (git commit -m 'feat: Add new feature X').
Push to the branch (git push origin feature/your-feature-name).
Open a Pull Request.
License
This project is open-source and available under the MIT License.