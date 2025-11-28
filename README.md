# TaskTable – Team Daily Reporting & Management System  
A Laravel-based platform for managing teams, members, daily tasks, work reports, and performance summaries.

Developed and maintained by **Aman Raj Dewangan**.

---

## 📌 Overview

TaskTable is a lightweight, modern, and powerful **Team Daily Reporting System** designed for organizations to manage:

- Daily reports from every team
- Per-member task assignments
- Work completion & submission tracking
- Team management (CRUD)
- Member management (Add/Remove/Leader support)
- Daily sheets auto-generated per team
- Summaries & analytics (Completed, In-Progress, Not-Completed)

The UI is optimized for clarity, speed, and smooth admin workflow.

---

## 🚀 Features

### **1. Team Management**
- Create, edit, and delete teams
- Rich description support
- Automatic count of team members
- Beautiful, animated team list sidebar
- Member management modal (Add/Remove/Leader)

### **2. Daily Reporting (Admin Panel)**
- Browse daily reports of every team
- Select specific dates using selector + last-6-day quick pills
- Summary cards:
  - Total Tasks
  - Completed
  - In-Progress
  - Not Completed
- Full tasks grouped by each team member
- Smooth animations, card elevation, and premium UI

### **3. Tasks & Sheets**
- Sheets auto-generated per team per day
- Each member sees assigned tasks
- Tracks:
  - Task description
  - Client (if applicable)
  - Leader remark
  - Member remark
  - Submission status
  - Work status (Completed/In-progress/Not-done)

### **4. Member Remarks with Expandable Preview**
- If remarks exceed a limit, they show a smart “View” option
- View full remark inside a modal

### **5. Fully AJAX-enhanced Member Management**
- Add/Remove members without leaving the dashboard
- Auto-refresh inside modal
- Leader update logic included

### **6. Modern UI/UX**
- Gradient badges
- Animated list items
- Soft fade transitions
- Glass-like card surfaces
- Sticky sidebar
- Responsive design for mobile and tablet

---

## 🛠️ Tech Stack

| Layer | Technology |
|------|------------|
| Backend | Laravel 12.x |
| Frontend | Blade, Bootstrap 5.3 |
| Database | MySQL / MariaDB |
| Auth | Session-based (Employee/Auth) |
| UI Enhancements | Custom CSS, Smooth Animations |
| Utilities | Carbon, Eloquent ORM |

---

## 📂 Project Structure (Important folders)

```

app/
Http/
Controllers/
Admin/
TeamController.php
DailySheetController.php
Models/
Team.php
TeamMember.php
DailySheet.php
Assignment.php

resources/
views/
admin/dashboard.blade.php
team/
index.blade.php
members.blade.php

routes/
web.php

public/
assets/

````

---

## ⚙️ Installation Guide

### **1. Clone Repository**

```bash
git clone https://github.com/YOUR_USERNAME/TaskTable.git
cd TaskTable
````

### **2. Install Dependencies**

```bash
composer install
npm install && npm run build   # optional if using compiled assets
```

### **3. Create Environment File**

```bash
cp .env.example .env
```

### **4. Generate Application Key**

```bash
php artisan key:generate
```

### **5. Configure Database**

Edit `.env`:

```
DB_DATABASE=tasktable
DB_USERNAME=root
DB_PASSWORD=
```

### **6. Run Migrations**

```bash
php artisan migrate
```

### **7. Start Development Server**

```bash
php artisan serve
```

App runs at:

```
http://127.0.0.1:8000
```

---

## 🧪 Dummy Data (Optional)

Run seeders:

```bash
php artisan db:seed
```

Includes:

* Default teams
* Sample employees
* Example daily sheets
* Sample assignments

---

## 🖥️ Usage Guide

### **Admin Dashboard**

URL:

```
/dashboard
```

You can:

* Select any team from sidebar
* Switch dates
* Review member-wise grouped tasks
* See remarks & submission status
* Manage team members
* Create or edit teams

### **Team Management**

Go to:

```
/team
```

You can:

* Create new teams
* Edit descriptions
* Manage members
* Remove members
* Assign leader roles

---

## 📤 Deployment (Production)

1. Configure `.env` for production
2. Enable caching:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

3. Set correct permissions:

```bash
chmod -R 775 storage bootstrap/cache
```

4. Use a proper queue worker if needed

---

## 👤 Author

**Aman Raj Dewangan**
Full-Stack PHP Developer & Cybersecurity Engineer
India

If you use this project or want enhancements, feel free to open issues or submit PRs.

---

## ⭐ Support

If you found this project helpful:

* Star the repository
* Share it with your team
* Contribute improvements

---

## 📜 License

This project is released under the **MIT License**.

---
