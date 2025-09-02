# 🎉 EWU Event App

this is a **group 5(section7)** CSE302 group project which is **Event Management System** built with **PHP, MySQL, HTML, and CSS**.  
This app allows **admins** to manage events, venues, meals, sponsors, logistics, and cashflow, while **students** can book events, make payments, and manage their profiles.

# submitted by #

Amit Datta

Sabik Hossen

Zahin Shariar

# submitted to #

Antu Chowdhury (ANTU),

Lecturer, Department of CSE

East West University

---

## 🚀 Features

### 👨‍💼 Admin Panel
- **Dashboard** – Quick overview of event stats and activities.  
- **Events Management** – Create, update, or delete events.  
- **Venues** – Manage venues with capacity details.  
- **Meals** – Set meal options for events.  
- **Bookings** – View and manage client bookings.  
- **Sponsors** – Add and track event sponsors.  
- **Logistics** – Manage logistics requirements for events.  
- **Cashflow** – Track income and expenses.  

### 👤 Client Panel
- **Dashboard** – Personalized overview of bookings and activities.  
- **Book Event** – Register for available events.  
- **My Bookings** – View and manage booked events.  
- **Feedback** – Share feedback on past events.  
- **Profile** – Update personal details.  
- **Payment** – Complete booking payments.  

---

## 🛠️ Installation Guide (XAMPP)

1. **Clone the Repository**
   ```bash
   git clone https://github.com/YOUR-USERNAME/ewu-event-app-v2.git
2. **unzip the file and paste it on the given adress**
# Example (Windows)
C:\xampp\htdocs\ewu-event-app-v2

3. Start Apache & MySQL in XAMPP Control Panel.
   
4. **create database**
> Open http://localhost/phpmyadmin

> Create a database ==ewu_event_app_v2==

5. **Import Database Schema & Seeds**

Import schema.sql → creates tables.(if you already created a database called ewu_event_app_v2 then skip the create database part)

Import seeds.sql → inserts sample data.

6. **database connection**
Open includes/db.php

Update with your database credentials:
```
$host = "localhost";
$user = "root"; // default XAMPP user
$pass = "";     // leave empty if no password
$dbname = "ewu_event_app_v2";
```
7. **run the app**

Visit: http://localhost/ewu-event-app-v2

Login using seeded credentials (check seeds.sql).

## 📂 Project Structure & Functionality

```
ewu-event-app-v2/
├── index.php # Login page (for both admin and client)
├── logout.php # Logout script, destroys session
├── schema.sql # Database structure (tables, columns)
├── seeds.sql # Sample data for testing/demo
├── includes/
│ ├── auth.php # Authentication logic (login verification)
│ └── db.php # Database connection setup
├── assets/
│ └── style.css # Main styling for all pages
├── admin/ # Admin-side functionalities
│ ├── dashboard.php # Admin homepage with stats overview
│ ├── events.php # Manage events: create, update, delete
│ ├── venues.php # Manage event venues and capacities
│ ├── meals.php # Manage meal options for events
│ ├── bookings.php # View/manage client bookings
│ ├── sponsors.php # Add and track sponsors
│ ├── logistics.php # Manage event logistics requirements
│ └── cashflow.php # Track income and expenses
└── client/ # Client-side functionalities
├── dashboard.php # Client homepage with bookings overview
├── book_event.php # Book an event
├── my_bookings.php# View and manage personal bookings
├── feedback.php # Submit feedback for past events
├── profile.php # Update personal profile details
└── payment.php # Make payments for booked events
```






