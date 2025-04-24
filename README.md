# phase-3
Final submission


# 💇‍♀️ Kaveesha Salon & Beauty Service Booking Web Application

## 📄 Description

**Kaveesha Salon & Beauty Service Booking Web Application** is a web-based platform that enables users to explore a variety of beauty and wellness services, view galleries, book appointments, and interact with staff—all from a modern, responsive interface. Designed to optimize both the customer and administrator experience, the system includes secure login, real-time availability checks, appointment booking, and user review management. It's built using a robust tech stack that includes HTML, CSS, JavaScript, PHP, and MySQL.

## 🚀 Features

- ✨ Clean and responsive UI for seamless use on desktop and mobile
- 🔐 Secure authentication system (login, registration, logout)
- 🗓️ Dynamic appointment booking and staff availability management
- 💬 Customer reviews and feedback system
- 📷 Gallery and categorized service images
- 👤 User profile management
- 📊 Admin-ready database structure for managing bookings and services

## 🏗️ Project Structure

```
kaveesha/
│
├── auth/                    # Authentication system (login, logout, register)
├── css/                     # Stylesheets
├── js/                      # JavaScript files
├── images/                  # Service and gallery images
│   ├── categories/
│   ├── gallery/
│   ├── services/
├── includes/                # Database connection and reusable code
├── database.sql             # SQL script to create necessary tables
├── about.php                # About us page
├── bookings.php             # User booking interface
├── contact.php              # Contact page
├── index.php                # Homepage
├── profile.php              # User profile
├── review.php               # Reviews page
├── services.php             # Service listing
├── get_available_times.php  # AJAX endpoint for available time slots
├── get_staff.php            # Staff fetch endpoint
└── process_cancel_booking.php # Cancel booking handler
```

## 🛠️ Technologies Used

- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP
- **Database:** MySQL
- **Server Environment:** XAMPP / WAMP / LAMP / Python HTTP server (for local testing)

## ⚙️ Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/YourUsername/kaveesha.git
   ```

2. **Navigate to the project directory**
   ```bash
   cd kaveesha
   ```

3. **Set up the database**
   - Import `database.sql` into your MySQL database using phpMyAdmin or command line.
   - Configure DB credentials in the database connection file (usually `includes/Database_Connection.php`).

4. **Start a local server**
   - Use tools like XAMPP, WAMP, or MAMP.
   - Place the project folder in `htdocs` (for XAMPP) and start Apache/MySQL.

5. **Open your browser and access**
   ```
   http://localhost/kaveesha/index.php
   ```

## 🤝 Contribution

Contributions are welcome! Feel free to fork the repo, submit a pull request, or open an issue to improve the project.

## 📄 License

This project is licensed under the **MIT License** – see the [LICENSE](LICENSE) file for details.

## 👤 Author

Developed by **Kaveesha**  
_Contact details or links can go here (optional)_

