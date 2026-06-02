# UIU ScholarNet — Academic Research & Collaboration Platform

A full-stack, high-fidelity academic research collaboration platform custom-built for UIU students and faculty to coordinate projects, manage tasks, share preprints, host discussion threads, and message colleagues.

---

## ⚙️ Tech Stack

- **Backend**: PHP 7.4+ (Vanilla, procedural and structured helper pattern)
- **Database**: MySQL 5.7+ / MariaDB (Driven by a custom mysqli parameterized statement wrapper)
- **Frontend**: Vanilla HTML5, CSS3 (Modular BEM-inspired stylesheets in `assets/`), and native JS for AJAX interactions (no bulky frontend frameworks)
- **Security**: Strict session management, global CSRF protection, and password hashing (`PASSWORD_DEFAULT` / `password_verify`)
- **Server**: Compatible with any standard Apache/PHP environment (specifically pre-configured for XAMPP)

---

## 🚀 Getting Started

### Prerequisites
- **XAMPP** (with Apache and MySQL enabled)
- **PHP 7.4+** with `mysqli` extension enabled
- A modern web browser (Chrome, Edge, or Firefox)

### Installation Steps

1. **Clone or Copy** the repository into your XAMPP server's `htdocs` directory:
   ```bash
   C:\xampp\htdocs\UiU-ScholarNet\
   ```

2. **Database Setup**:
   - Open your browser and go to phpMyAdmin: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
   - Click the **"Import"** tab.
   - Select the `database.sql` file located in the root of the project.
   - Click **"Go"** to initialize the `uiu_scholarnet` database and load seed data.

3. **Configure the database**:
   This project reads DB credentials from environment variables (recommended), with safe defaults for XAMPP.
   - `DB_HOST` (default `127.0.0.1`)
   - `DB_USER` (default `root`)
   - `DB_PASS` (default empty)
   - `DB_NAME` (default `uiu_scholarnet`)
   - `EMAIL_USER` (Gmail address for password reset emails)
   - `EMAIL_PASS` (Gmail App Password)
   - `EMAIL_HOST` (default `smtp.gmail.com`)
   - `EMAIL_PORT` (default `587`)

   Configure connection credentials inside the `.env` file:
   ```ini
   DB_HOST=127.0.0.1
   DB_USER=root
   DB_PASS=
   DB_NAME=uiu_scholarnet
   DB_PORT=3306
   EMAIL_USER=your-email@gmail.com
   EMAIL_PASS=your-gmail-app-password
   EMAIL_HOST=smtp.gmail.com
   EMAIL_PORT=587
   ```
   *Note: Defaults are pre-configured to match standard XAMPP out-of-the-box settings.*

4. **Run the Application**:
   - Start MySQL and Apache inside your XAMPP Control Panel.
   - Alternatively, use the helper batch script to start PHP's built-in development server:
     - Run `start-server.bat` to launch the server on [http://localhost:8000](http://localhost:8000).
     - Run `start-with-mysql.bat` to automatically open the XAMPP Control Panel and start the PHP development server.
   - Access the site via [http://localhost:8000](http://localhost:8000) or through XAMPP via [http://localhost/UiU-ScholarNet/](http://localhost/UiU-ScholarNet/).

---

## 📁 Project Structure

```
UiU-ScholarNet/
├── index.php                  # Landing page
├── database.sql               # Database schema & initial seeds
├── start-server.bat           # Built-in PHP server launch script
├── start-with-mysql.bat       # XAMPP & PHP server launch script
│
├── auth/                      # Authentication views
│   ├── login.php              # Login panel
│   ├── register.php           # User signup panel
│   └── forgot_password.php    # Password reset view
│
├── dashboard/                 # Application dashboard pages
│   ├── index.php              # Home dashboard
│   ├── collaboration.php      # Collaboration finder board
│   ├── projects.php           # Project administration interface
│   ├── tasks.php              # Kanban board
│   └── ...                    # Chat, Document Editor, Preprints, Resource Hub
│
├── actions/                   # Backend request handlers
│   ├── route.php              # POST request router
│   ├── admin_misc/            # Category 1: Admin & Miscellaneous handlers
│   ├── discussion_preprints/  # Category 2: Discussion & Preprint handlers
│   ├── collaboration_messaging/# Category 3: Chat & Collaboration handlers
│   ├── project_task_document/ # Category 4: Project, Tasks & Docs handlers
│   ├── auth_user/             # Category 5: Signin, Signup & profile handlers
│   └── [compatibility wrappers]# Root wrappers forwarding requests to subfolders
│
├── includes/                  # Common library routines
│   ├── session.php            # Secure session initialization functions
│   ├── db_connect.php         # Environment loader & MySQL connection
│   ├── csrf.php               # Anti-CSRF token helpers
│   ├── alerts.php             # Toast / notification system
│   ├── header.php             # Page structure header
│   └── sidebar.php            # Sidebar navigation panel
│
├── assets/                    # Static UI resources
│   ├── css/                   # Styled components (sidebar, kanban, main layouts)
│   └── js/                    # Dynamic client-side logic
│
└── uploads/                   # User-uploaded papers & resources
```

---

## 🏛️ System Architecture

### 1. Database Wrapper & Helper
All database access is centralized through the custom `db_query()` function defined in [includes/db_connect.php](file:///d:/UiU/7th%20trimester/Web%20Programming/UiU-ScholarNet/includes/db_connect.php).
- **Auto-Type Binding**: The helper automatically evaluates parameters (e.g. integer `i`, double `d`, or string `s`) to prevent manual type mappings, keeping backend logic readable and minimal.
- **SQL Injection Defense**: Prepared statements are strictly enforced via parameterization, eliminating direct concatenation vectors.
```php
$result = db_query(
    "SELECT * FROM users WHERE email = ? AND role = ?",
    [$email, $role]
);
```

### 2. Request Routing and Compatibility
Form action endpoints use a hybrid wrapper structure:
- **Central Routing Router**: [actions/route.php](file:///d:/UiU/7th%20trimester/Web%20Programming/UiU-ScholarNet/actions/route.php) serves as the primary controller dispatch hub for application action triggers via action name payload routing.
- **Backward Compatibility Wrappers**: Legacy dashboard links pointing to `../actions/filename.php` remain completely intact. We achieve this by hosting thin wrapper files at the root of `actions/` that simply `include` the actual implementation file from their respective subfolders. For example:
  ```php
  <?php
  // actions/login.php
  include __DIR__ . '/auth_user/login.php';
  ```
- **Context Preservation**: Having scripts execute in the context of the root folder ensures that all standard redirects (e.g., `header("Location: ../dashboard/index.php")`) continue to resolve perfectly.

### 3. Security Lifecycle
```
Client Request (POST)
   │
   ├──► CSRF Validation ──► [includes/csrf.php] (csrf_validate_or_die)
   │
   ├──► Session Start   ──► [includes/session.php] (start_secure_session)
   │                           - HttpOnly, Strict cookie flags, Strict mode
   │
   └──► DB Queries      ──► [includes/db_connect.php] (db_query)
                               - Automatic parameter binding / protection
```

---

## 🔑 Environment Variables

| Variable | Description | Default |
|---|---|---|
| `DB_HOST` | Database host IP or domain name | `127.0.0.1` |
| `DB_USER` | Database user account name | `root` |
| `DB_PASS` | Database user connection password | *(empty)* |
| `DB_NAME` | Database schema name | `uiu_scholarnet` |
| `DB_PORT` | MySQL database connection port | `3306` |

---

## 🛠️ Troubleshooting

### Database connection fails
- Ensure MySQL server is active in XAMPP.
- Verify configuration values inside the `.env` file match your MySQL setup.
- If using port forwarding or custom ports, adjust `DB_PORT` accordingly.

### Redirects or relative paths are broken
- Ensure you have placed the project in `C:\xampp\htdocs\UiU-ScholarNet\`.
- All backend-to-backend references use `__DIR__` to prevent relative execution faults. Do not remove `__DIR__` references when updating internal imports.

### File uploads failing
- Verify that PHP has write access to the `uploads/` folder in the project directory.
- Check maximum upload sizes inside your `php.ini` file if uploading large preprints or datasets.
