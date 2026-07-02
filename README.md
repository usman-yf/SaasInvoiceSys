# SaasInvoiceSys - Premium Invoice Management System

SaasInvoiceSys is an enterprise-grade, highly secure Invoice and Inventory Management System. Designed with a stunning, modern user interface using **Tailwind CSS**, it empowers businesses to effortlessly track customers, manage product inventories, issue professional invoices, and monitor comprehensive business analytics in real time.

Built with **PHP 8.1+** and **MySQL**, the system prioritizes security with features like **Two-Factor Authentication (2FA)**, Email Verification, and granular User Roles.

---

## 🌟 Comprehensive Features

### 🔐 Advanced Authentication & Security

- **Multi-layered Login:** Passwords are securely hashed using modern bcrypt algorithms.
- **Two-Factor Authentication (2FA):** Integrated email-based OTP verification. Users receive a 6-digit code upon login which dynamically expires. Features an animated, auto-focusing input interface.
- **Email Verification:** New administrator and user accounts must verify their email addresses via secure, timed cryptographic tokens.
- **Activity Logging:** Every critical action (logging in, creating invoices, deleting customers) is meticulously recorded with timestamps and user references in the `logs` table.

### 📊 Intelligent Dashboard & Analytics

- **Live KPIs:** Instantly view Total Revenue, Pending Invoices, Active Customers, and Monthly Growth.
- **Visual Feedback:** Interactive charts and numerical breakdowns of recent transactions.
- **Premium UI:** Glassmorphism accents, soft layered shadows, and high-quality micro-interactions using native CSS transitions.

### 🧾 Complete Invoice Lifecycle Management

- **Create & Edit:** Rapidly build invoices by selecting from saved customers and products. Tax and discount algorithms automatically calculate sub-totals and grand totals.
- **Status Tracking:** Automatically track whether an invoice is *Paid*, *Unpaid*, or *Partial*.
- **Direct Emailing:** Invoices can be securely emailed directly to the client's inbox from the dashboard, powered by **PHPMailer** and your custom SMTP configurations.
- **QR Code Verification:** Invoices feature dynamically generated QR codes that, when scanned, link to a secure online verification portal.

### 👥 Customer & Inventory Management

- **Client Database:** Maintain a robust ledger of client details (Email, Phone, Billing Address).
- **Product Catalog:** Manage SKUs, unit prices, and inventory descriptions.
- **Instant Search:** Ajax-powered data tables allow for instant filtering and sorting of large datasets without reloading the page.

---

## 🏗️ Project Architecture & Structure

The codebase is organized in a modular, scalable architecture:

```text
SaasInvoiceSys/
├── ajax/               # Asynchronous endpoints (live search, notification handlers)
├── assets/             # Static UI assets (Tailwind CSS, Vanilla JS, images, icons)
├── auth/               # Security layer (login.php, 2fa.php, logout.php, verify.php)
├── config/             # Environment configs (db.php, mail.php)
├── customers/          # Client management module (CRUD operations)
├── includes/           # Core logic (functions.php, header.php, footer.php, mailer.php)
├── invoices/           # Invoice management (create, edit, email engine, list)
├── lib/                # External dependencies (PHPMailer engine)
├── payments/           # Financial transaction tracking
├── products/           # Inventory and catalog management
├── users/              # RBAC (Role-Based Access Control) and user administration
├── database.sql        # Complete MySQL database schema and default seeds
├── index.php           # Core Dashboard controller
├── logs.php            # Security and system audit trails
├── reports.php         # Analytics and data export generation
└── settings.php        # Global system configuration (Company Name, 2FA settings, etc.)
```

---

## 🛠️ Tech Stack & Libraries

- **Backend Logic:** PHP 8.1+ (Procedural & Object-Oriented patterns used strategically)
- **Database:** MySQL / MariaDB (Relational schema with foreign key constraints)
- **Styling:** Tailwind CSS (Utility-first framework for pixel-perfect, responsive design)
- **Interactivity:** Vanilla JavaScript (ES6+), DOM manipulation without heavy frameworks like React/Vue for maximum performance.
- **Email Delivery:** PHPMailer (Configured for modern SMTP servers like Gmail, Mailtrap, or SendGrid)
- **UI Components:**
  - *Icons:* [Lucide Icons](https://lucide.dev/)
  - *Tooltips:* [Tippy.js](https://atomiks.github.io/tippyjs/) for beautiful, animated popovers.

---

## 🚀 Installation & Setup Guide

### 1. Environment Preparation

Ensure you have a local web server environment like **XAMPP, WAMP, or MAMP** installed, running PHP 8.1+ and MySQL.

### 2. Clone the Repository

Clone this repository directly into your web server's root directory (e.g., `C:\xampp\htdocs\inv`).

```bash
git clone https://github.com/usman-yf/SaasInvoiceSys.git
cd SaasInvoiceSys
```

### 3. Database Configuration

1. Open your MySQL management tool (e.g., phpMyAdmin) and create a new database named `invoice_db`.
2. Import the provided schema:

   ```bash
   mysql -u root -p invoice_db < database.sql
   ```

3. Open `config/db.php` and verify the database connection credentials match your local environment.

### 4. Mail Server (SMTP) Setup

To enable 2FA codes, account verification, and invoice emailing, you must configure your SMTP server.

1. Open `config/mail.php`.
2. Enter your SMTP Host, Port, Username, and Password.
   > **Note:** If using Gmail, you must generate an "App Password" from your Google Account Security settings.

### 5. Access the Application

Navigate to `http://localhost/inv` in your browser.
Log in using the default administrator credentials (if seeded in your database) and immediately navigate to the `Settings` panel to customize your Company Profile.

---

## 📸 Interface Gallery

### Beautiful Dashboard Insights

![Dashboard](assets/img/dashboard.png)

### Streamlined Invoice Generation

![Invoices](assets/img/invoice.png)

### Client Relationship Management

![Customers](assets/img/customer.png)

### Product Catalog

![Products](assets/img/products.png)

### Role-Based User Management

![Users](assets/img/users.png)

### Advanced Analytics & Reporting

![Reports](assets/img/report.png)

### Security Audit Logs

![Activity Logs](assets/img/logs.png)

---

## 📄 License & Ownership

This project is proprietary and confidential to **usman-yf**. All rights reserved. Unauthorized copying, modification, distribution, or use of this software, via any medium, is strictly prohibited without explicit permission.
