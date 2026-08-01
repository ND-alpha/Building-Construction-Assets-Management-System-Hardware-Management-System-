# Hardware Management System - HND IT Final Project
A robust, standalone hardware management solution developed as an individual HND IT Final Project to efficiently track, manage, and monitor hardware inventory, user allocations, and asset lifecycle. (Note: While this current implementation is a standalone desktop/local system, it is architected with future enhancements in mind to scale into a fully web-based platform).

----

## 👥 Project Type
* **Individual Project (HND IT Final Project)**

---

## 🛠️ Tech Stack & Technologies Used

* **Frontend / UI: HTML5, CSS3, JavaScript, Bootstrap**
* **Backend:** PHP
* **Database:** MySQL / MariaDB (hardware_db)
* **Server Environment:** Standalone / Local Server Setup (XAMPP / Apache / MySQL)

---

✨ Key Features & Modules

### 👤 User Roles & Authentication
* Secure **login** and **role-based** access control for managing system privileges.
* Profile management and administrative controls.

### 💻 Hardware & Inventory Management
* **Asset Registration:** Complete hardware tracking with serial numbers, specifications, models, and purchase details.
* **Inventory Control:** Real-time monitoring of stock levels, availability, and item categorization.

### 🔄 Allocation & Maintenance Tracking
* Assigning hardware assets to users or departments with checkout/check-in logs.
* Tracking maintenance history, repairs, and retired asset statuses.

### 📊 Reporting & Logs
* Generating activity logs and reports for audits and asset distribution analysis.

---

### 💻 My Contributions (Individual Project)

* **As the sole developer of this HND IT final project, my core contributions included:**
* **System Architecture & Design:** Designed the standalone system structure and database relationships.
* **Core Modules Development:** Built the end-to-end hardware tracking, inventory management, and allocation modules.
* **Database Integration:** Created relational tables and connected them seamlessly with PHP backend logic.
* **UI/UX Design:** Developed a responsive and clean user interface utilizing Bootstrap and modern web standards.

---

### 🗄️ Database Architecture

The system uses the `hardware` database, containing the following core tables:
* `users` : Stores system user accounts, credentials, and access roles.
* `hardware_items` : Stores detailed hardware specifications, categories, and serial numbers.
* `allocations` : Tracks hardware assignments, user check-outs, and return dates.
* `maintenance_logs` : Records hardware repair and service history.
* `categories` : Defines classifications for different hardware types.

---

### 🚀 Future Enhancements

* **Web-Based Migration:** Transitioning the standalone architecture into a fully scalable, cloud-ready web application accessible via remote servers.
* **Advanced Analytics:** Implementing automated reporting dashboards with graphical asset utilization insights.
* **Barcode/QR Code Integration:** Adding scanner support for faster asset check-in and check-out processes.

---

## ⚙️ Local Installation & Setup
1. **Clone the Repository:**
   ```bash
   https://github.com/ND-alpha/Building-Construction-Assets-Management-System-Hardware-Management-System-.git
