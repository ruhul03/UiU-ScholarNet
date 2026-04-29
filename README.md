# UIU ScholarNet 🎓
**Research & Collaboration Platform for United International University**

UIU ScholarNet is a premium, full-stack academic platform designed to help students and faculty connect, collaborate, and manage research projects efficiently.

---

## 🚀 Key Features
- **Collaboration Finder**: Post and browse research opportunities within the university.
- **Project Management**: Track research progress with visual boards and milestones.
- **Task Board (KanBan)**: Manage project-specific tasks with priority levels and due dates.
- **Resource Hub**: Share and download academic papers, datasets, and lecture notes.
- **Premium Auth**: Secure student/faculty registration and login with session management.

---

## 📂 Project Structure
```text
/UiU-ScholarNet
│
├── /actions            # Backend processing logic (Login, Register, CRUD)
├── /assets             # Stylesheets and media
│   └── /css            # Premium CSS system
├── /auth               # Authentication interfaces
├── /dashboard          # Main workspace modules
├── /includes           # Reusable components (Sidebar, DB Connect, Auth guards)
├── database.sql        # MySQL database schema
└── index.php           # Landing Page
```

---

## 🛠 Setup Instructions

### 1. Requirements
- XAMPP or any PHP 8+ / MySQL environment.

### 2. Database Setup
1. Open **phpMyAdmin**.
2. Create a database named `uiu_scholarnet`.
3. Import the `database.sql` file provided in the root directory.

### 3. Configuration
1. Open `includes/db_connect.php`.
2. Update the `$user` and `$pass` variables to match your local MySQL credentials.

### 4. Running the App
1. Place the project folder in your web server root (e.g., `C:/xampp/htdocs`).
2. Start Apache and MySQL from the XAMPP Control Panel.
3. Access the app at `http://localhost/UiU-ScholarNet` (or use `php -S localhost:8000` in the root folder).

---

## 🎨 Design System
- **Colors**: Deep Navy (#0a1128), Muted Gold (#c5a022), Cream (#f8f7f2).
- **Typography**: 'Playfair Display' (Headings), 'Inter' (Body).

---

**Developed for UIU Students & Researchers.**
