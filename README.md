# UIU ScholarNet — Research & Collaboration Platform

A full-stack academic research collaboration platform built for UIU students and faculty. Manage projects, coordinate tasks, share resources, and communicate — all in one premium interface.

## 🚀 Quick Setup

### Prerequisites
- **XAMPP** (Apache + MySQL running)
- **PHP 7.4+** with `mysqli` extension
- Browser (Chrome/Edge recommended)

### Installation Steps

1. **Clone or copy** the project to your XAMPP `htdocs` directory:
   ```
   C:\xampp\htdocs\UiU-ScholarNet\
   ```
   Or use XAMPP to serve from the current directory.

2. **Import the database** via phpMyAdmin:
   - Open `http://localhost/phpmyadmin`
   - Click **"Import"** tab
   - Select `database.sql` from the project root
   - Click **"Go"**

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

4. **Access the site**:
   ```
   http://localhost/UiU-ScholarNet/
   ```

## 📁 Project Structure

```
UiU-ScholarNet/
├── index.php                  # Landing page
├── database.sql               # Full schema + seed data
│
├── auth/                      # Authentication logic
│
├── dashboard/                 # Main application pages
│   ├── index.php              # Dashboard home
│   ├── collaboration.php      # Collaboration Finder
│   ├── projects.php           # Project management
│   ├── tasks.php              # Kanban task board
│   ├── edit_project.php       # Premium project curation
│   └── ...                    # Other modules (Editor, Messages, etc.)
│
├── actions/                   # Backend controllers
│   ├── add_task.php           # Task delegation & creation
│   ├── delete_project.php     # Permanent removal
│   ├── clear_completed_tasks.php # Kanban board cleanup
│   └── delete_collaboration_post.php # Request lifecycle management
│
├── includes/                  # Shared components
│   ├── alerts.php             # Unified session-based notification system
│   ├── sidebar.php            # Optimized navigation
│   └── ...
│
├── assets/css/                # Modular CSS architecture (BEM-inspired)
└── uploads/                   # Secure research storage
```

## ⚙️ Key Features

- **University Research Matrix**: Live dashboard tracking personal research progress, pending tasks, and collaboration requests.
- **Institutional Repository**: Full-lifecycle project management with visibility controls (Public/Private/Institution) and progress tracking.
- **Kanban Logistics**: Advanced task management with assignment delegation, priority levels, and one-click board clearing.
- **Collaboration Finder**: Interdisciplinary discovery board with project-linking capabilities and owner-controlled request lifecycle.
- **Premium Curation**: High-fidelity project editing suite with support for research abstracts and metadata refinement.
- **Unified Alert System**: Standardized session-based notifications for a seamless, "no-ghost" user experience.
- **Secure File Manager**: Drag-and-drop research material storage with automated size formatting and type validation.

## 🎨 Design System

- **Typography**: Playfair Display (Headings), Inter (Body)
- **Palette**: Deep Navy (`#0a1128`), UIU Gold (`#c5a022`), Soft Slate (`#f5f5f5`)
- **Aesthetic**: Minimalist high-fidelity dashboard with glassmorphism effects and modular CSS.

## 📝 License

Built for the UIU Web Programming course — 7th Trimester, 2026.
Designed by Antigravity AI.
