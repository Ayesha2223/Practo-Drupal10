# Practo – Healthcare Appointment Platform (Drupal 10)

A complete **doctor discovery and appointment booking system** built using **Drupal 10**, with patient workflows, doctor dashboards, admin verification, custom modules, and structured Drupal entities.

---

## 1. Overview

The **Practo Healthcare Platform** allows:

- **Patients** to search doctors, view profiles, book appointments, and track bookings  
- **Doctors** to manage appointment requests and confirm/reject bookings  
- **Admins** to verify doctors, manage listings, and monitor statistics  

This system is built using:

- Custom Drupal 10 modules  
- Controllers, Forms, Twig templates  
- Drupal content types for Doctors and Appointments  

---

## 2. Features

### ✅ Patient Features
- Patient registration  
- Search & filter doctors  
- View doctor details  
- Book appointments  
- Track appointments  
- Cancel appointments  
- Unique booking ID generation  

### ✅ Doctor Features
- Doctor registration  
- Doctor dashboard  
- View upcoming + past appointments  
- Confirm or reject patient bookings  
- Filter appointments (active, completed, cancelled, all)  

### ✅ Admin Features
- Admin dashboard  
- Verify doctor profiles  
- Manage all doctors  
- Manage all appointments  
- Bulk actions (publish / unpublish / delete)  
- Quick search for doctors and appointments  

---

## 3. Technology Stack

| Layer       | Technology      |
|------------|------------------|
| CMS        | Drupal 10        |
| Backend    | PHP 8+           |
| Frontend   | Twig, CSS, JS    |
| Database   | MySQL (XAMPP)    |
| Web Server | Apache           |
| Build Tool | Composer         |
| CLI Tool   | Drush            |

---

## 4. Custom Modules

### 🔹 practo_core
Implements:
- Doctor listing  
- Doctor profile  
- Search and filtering  
- Appointment booking logic  
- Dashboards (patient, doctor, admin)  
- Verification workflow  
- Controllers, Forms, Twig templates  

### 🔹 practo_appointments
Implements:
- Appointment field structure  
- Appointment-related logic  

---

## 5. Key Routes

| Feature             | URL                                      |
|---------------------|-------------------------------------------|
| Doctor Listing      | `/doctors`                                |
| Doctor Profile      | `/doctor/{id}`                            |
| Search Doctors      | `/search-doctors`                         |
| Book Appointment    | `/book-appointment/{doctor}`              |
| Patient Dashboard   | `/user/appointments`                      |
| Doctor Dashboard    | `/doctor/dashboard`                       |
| Admin Dashboard     | `/admin/practo/dashboard`                 |
| Verify Doctors      | `/admin/practo/doctor-verification`       |

---

