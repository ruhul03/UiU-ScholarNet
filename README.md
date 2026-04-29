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

   You can also create a `.env` file in the project root (same folder as `index.php`) and the app will read it automatically.

4. **Access the site**:
   ```
   http://localhost/UiU-ScholarNet/
   ```

### Default Login Credentials
| Email | Password | Role |
|-------|----------|------|
| `sabbir@uiu.ac.bd` | `password` | Student |

## 📁 Project Structure

```
UiU-ScholarNet/
├── index.php                  # Landing page
├── database.sql               # Full schema + seed data
│
├── auth/                      # Authentication
│   ├── login.php
│   ├── register.php
│   └── logout.php
│
├── dashboard/                 # Main application pages
│   ├── index.php              # Dashboard home
│   ├── collaboration.php      # Collaboration Finder
│   ├── projects.php           # Project management
│   ├── tasks.php              # Kanban task board
│   ├── document_editor.php    # Rich text editor
│   ├── messages.php           # Team messaging
│   ├── file_upload.php        # File manager
│   └── resources.php          # Resource hub
│
├── actions/                   # Backend processors
│   ├── login.php
│   ├── register.php
│   ├── create_project.php
│   ├── add_task.php
│   └── post_collaboration.php
│
├── includes/                  # Shared components
│   ├── db_connect.php         # Database connection
│   ├── auth_check.php         # Auth guard (prepared statements)
│   └── sidebar.php            # Navigation sidebar
│
├── assets/css/                # Modular CSS architecture
│   ├── style.css              # Import hub (entry point)
│   ├── global.css             # Variables, resets, buttons
│   ├── layout.css             # Sidebar, main-content grid
│   ├── components.css         # Modals, progress bars, cards
│   ├── auth.css               # Login/register pages
│   ├── landing.css            # Landing page
│   ├── dashboard.css          # Dashboard home
│   ├── collaboration.css      # Collaboration finder
│   ├── projects.css           # Project cards & lists
│   ├── tasks.css              # Kanban board
│   ├── editor.css             # Document editor
│   └── messages.css           # Team messaging
│
└── uploads/                   # User-uploaded files
```

## 🗄️ Database Schema

| Table | Purpose |
|-------|---------|
| `users` | Student/faculty accounts with skills, points |
| `projects` | Research projects with status, progress tracking |
| `tasks` | Kanban tasks linked to projects |
| `collaboration_posts` | Discovery board for finding partners |
| `resources` | Uploaded files (PDFs, datasets, etc.) |
| `messages` | Team communication messages |
| `documents` | Rich text documents with versioning |

## 🎨 Design System

- **Fonts**: Playfair Display (headings), Inter (body)
- **Colors**: Navy (`#0a1128`), Gold (`#c5a022`), Cream (`#f8f7f2`)
- **Icons**: Font Awesome 6
- **Avatars**: UI Avatars API

## ⚙️ Features

- ✅ User registration & authentication
- ✅ Dashboard with live stats
- ✅ Project creation & management
- ✅ Kanban task board (To Do / Done)
- ✅ Collaboration discovery & posting
- ✅ File upload with drag-and-drop
- ✅ Resource library
- ✅ Document editor workspace
- ✅ Team messaging interface
- ✅ Modular CSS architecture (12 files)

## 📝 License

Built for UIU Web Programming course — 7th Trimester, 2026.
