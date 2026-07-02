# InvSys - Premium Invoice Management System

InvSys is a modern, feature-rich Invoice Management System built with PHP and MySQL, designed with a premium, responsive user interface using Tailwind CSS. It allows businesses to seamlessly manage customers, products, and invoices, track business analytics, and maintain rigorous security with Two-Factor Authentication.

## 🌟 Features

- **Advanced Authentication & Security**
  - Secure Login with Password Hashing.
  - **Two-Factor Authentication (2FA)** with 6-digit OTP codes sent via email.
  - Interactive, animated OTP input with dynamic countdown timers.
  - Detailed system activity logs.

- **Dashboard & Analytics**
  - Real-time statistics and Key Performance Indicators (KPIs).
  - Clean, accessible, and premium UI design.

- **Invoice Management**
  - Create, edit, and manage professional invoices.
  - Built-in tax and discount calculations.
  - Print to PDF or send invoices directly to clients via email (powered by PHPMailer).
  - Built-in QR codes on invoices for secure verification.

- **Customer & Product Management**
  - Add and manage a database of clients.
  - Inventory and product management with SKUs and pricing.
  - Quick-search and filter capabilities across data tables.

- **Premium User Interface**
  - Built with **Tailwind CSS** for a highly responsive, modern design.
  - Micro-interactions, beautiful tooltips (Tippy.js), and Lucide icons.
  - Glassmorphism effects, soft shadows, and clean typography.
  - Toast notifications and premium modal dialogs.

## 📸 Screenshots

### Dashboard
![Dashboard](assets/img/dashboard.png)

### Invoices
![Invoices](assets/img/invoice.png)

### Customers
![Customers](assets/img/customer.png)

### Products
![Products](assets/img/products.png)

### Users & Roles
![Users](assets/img/users.png)

### Reports
![Reports](assets/img/report.png)

### Activity Logs
![Activity Logs](assets/img/logs.png)

## 🚀 Installation & Setup

1. **Clone the Repository**
   Clone this project to your local web server directory (e.g., `htdocs` for XAMPP).
   ```bash
   git clone https://github.com/yourusername/invsys.git
   cd invsys
   ```

2. **Database Setup**
   - Create a new MySQL database named `invoice_db`.
   - Import the included database structure and default data:
     ```bash
     mysql -u root -p invoice_db < database.sql
     ```

3. **Configure the Environment**
   - Open `config/db.php` and configure your database connection parameters.
   - Open `config/mail.php` and set up your SMTP credentials (e.g., Gmail, Mailtrap) for 2FA, Verification, and Invoice emails.

4. **Default Login**
   - The default administrator account (if present in your SQL dump) can be used to log in and configure the application settings from the dashboard.

## 🛠️ Technologies Used

- **Backend:** PHP 8.1+, MySQLi
- **Frontend:** HTML5, Tailwind CSS, Vanilla JavaScript
- **Libraries:** PHPMailer, Tippy.js (Tooltips), Lucide Icons
- **Server Environment:** Designed for Apache (XAMPP/LAMP stack)

## 📄 License

This project is proprietary and confidential. All rights reserved.
