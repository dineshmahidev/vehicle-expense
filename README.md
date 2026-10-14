# Shree Laxmi Expenses — Lorry Fleet & Expense Management

A modern, full-featured web application built with **PHP 8.2**, **MySQL (PDO)**, **HTML5**, **Modern CSS3**, and **Vanilla JavaScript** (with Chart.js & html2pdf.js).

---

## 🚀 Quick Access & URL

Access the project directly in your browser:
**[http://localhost/vehicle/](http://localhost/vehicle/)** (or [http://localhost/vehicle/login.php](http://localhost/vehicle/login.php))

---

## 🔑 Demo Login Credentials (1-Click Test Chips on Login Page)

| Role | Username | Password | Purpose |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `admin` | `admin123` | Full access to all branches, fleet, reports, and assignments |
| **Branch Manager** | `murugan` | `admin123` | Salem Branch hub management & ledger |
| **Driver 1** | `driver1` | `admin123` | P. Arumugam (Driver portal, mobile expense logger) |
| **Driver 2** | `driver2` | `admin123` | S. Rajendran |
| **Driver 3** | `driver3` | `admin123` | M. Velusamy |

---

## ✨ Features Built

1. **Modern FinTech Logistics UI**:
   - High-contrast, clean typography (`Plus Jakarta Sans` Google Font).
   - Glassmorphism & card elevation with subtle ambient shadows.
   - Mobile-first responsive design for drivers entering diesel/toll receipts on the highway.
2. **Branch Management (`branches.php`)**:
   - Create regional logistics hubs (Salem, Chennai, Coimbatore, etc.).
   - Branch-level profit & loss, fleet counts, and contact directory.
3. **Lorry Fleet Management (`lorries.php`)**:
   - Registration number, model, axle/body type (10-wheeler, 14-wheeler, container, etc.), tonnage, odometer.
   - Operational status (Active, Maintenance, Idle).
4. **Static & Dynamic Driver Assignment (`assignments.php`)**:
   - **Static Assignment**: Permanent default driver roster.
   - **Dynamic Assignment**: Assign drivers dynamically for specific route trips (e.g. *Salem -> Mumbai -> Delhi*) with start/end odometer readings and route instructions.
5. **Driver Directory (`drivers.php`)**:
   - Driving license tracking, home branch, assigned vehicle, and total logged expenses.
6. **Smart Income & Expense Entry (`add-transaction.php`)**:
   - Automatic driver & branch detection when selecting a lorry.
   - **Diesel Smart Calculator**: Automatically calculates `Fuel Liters × Rate = Total Amount`.
   - File attachment for photographing petrol pump slips and toll bills.
7. **Unified Income & Expense Ledger (`transactions.php`)**:
   - Date range, Lorry, Driver, Branch, and Category filtering.
   - 1-click **Export to CSV / Excel**.
8. **Print & PDF Generation (`lorry-report.php` & `voucher.php`)**:
   - **Lorry P&L Statement**: Complete financial balance sheet per lorry, fuel efficiency (KM/Litre), cost per KM, and official letterhead signature blocks.
   - **Payment / Receipt Voucher**: Individual official transport slips with Indian Rupee words conversion and signature lines.
   - 1-click **Download Official PDF** and **Print Report**.
