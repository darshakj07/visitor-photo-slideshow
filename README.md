# VISITOR PHOTO SLIDESHOW
## MCA Assignment 1: Programming and Debugging Using LLMs
### Core PHP + MySQL + Vanilla JS + Bootstrap 5

A modern, responsive, professional web application designed for events, exhibitions, and visitor photo walls. Visitors register their name and upload photos, which appear in an automated, high-definition public slideshow screen upon approval and explicit consent. Returning visitors are recognized via secure cryptographic access keys and PINs, allowing them to view their gallery, upload additional photos, manage privacy consent, or remove photos.

---

### Key Features

1. **Modern Responsive Landing Page:**
   - Hero section, intuitive call-to-actions, workflow explanation, and privacy principles.
2. **Visitor Registration & Multi-Photo Upload:**
   - Multi-image selection with client-side drag-and-drop and instant thumbnail preview.
   - Per-file removal from preview, file name and size badges.
   - Strict maximum 10 MB per image and maximum 10 images per batch.
3. **Robust Backend Validation:**
   - Multi-tier MIME validation using PHP `finfo` and `getimagesize()` against JPEG, PNG, and WebP.
   - Cryptographic random filename generation prevents collisions and path traversal.
   - Non-executable upload directory protected with `.htaccess`.
4. **Returning Visitor Identification:**
   - Secure cryptographic token (`bin2hex(random_bytes(32))`) and optional 6-digit PIN.
   - Never relies on plain email alone as an authentication mechanism.
   - Aggregates new uploads with the visitor's existing photo records.
5. **Visitor Management Dashboard:**
   - Private view of all uploaded photos with upload dates and approval status.
   - Instant consent management (grant or withdraw public display permissions).
   - Ability to delete individual photos with automated file unlinking.
6. **Smart TV & Projector Ready Public Slideshow:**
   - Fullscreen mode, crossfade transitions, configurable interval (default 8 seconds).
   - Keyboard navigation (Space for pause/resume, Arrows for prev/next, F for fullscreen).
   - Live asynchronous polling updates the queue dynamically as new visitors submit photos.
   - Displays visitor display names only when explicit consent is active.
7. **Protected Host / Admin Dashboard:**
   - Secure authentication via `password_hash()` and `password_verify()`.
   - Real-time statistics: visitor count, total photos, active slideshow photos.
   - Content moderation: approve, hide, or permanently delete photos.
   - Slideshow parameter controls (interval, captions).

---

### Technology Stack

- **Backend:** Core PHP 8.2+ (No frameworks, pure PDO, strict types, secure sessions).
- **Database:** MySQL / MariaDB (Relational schema, foreign keys, transactions, prepared statements).
- **Frontend:** HTML5, CSS3, Bootstrap 5.3, Font Awesome 6, Vanilla JavaScript (ES6+).
- **Environment:** XAMPP on Windows (Apache + MySQL/MariaDB).

---

### Installation & XAMPP Setup Guide

#### 1. Clone Repository into XAMPP
```bash
cd C:\xampp\htdocs
git clone https://github.com/darshakj07/visitor-photo-slideshow.git
cd visitor-photo-slideshow
```

#### 2. Environment Configuration
Copy `.env.example` to `.env`:
```bash
copy .env.example .env
```
*(If your XAMPP MariaDB is configured on port 3307 or 3306, adjust `DB_PORT` in `.env` accordingly).*

#### 3. Database Initialization
1. Ensure MySQL / MariaDB is running in XAMPP.
2. Import the database schema:
```bash
mysql -u root -p -P 3307 < database/schema.sql
mysql -u root -p -P 3307 < database/seed.sql
```
*(Or import via phpMyAdmin at `http://localhost/phpmyadmin`).*

#### 4. Access the Application
Open your web browser and navigate to:
- **Landing Page:** `http://localhost/visitor-photo-slideshow/`
- **Register & Upload:** `http://localhost/visitor-photo-slideshow/visitor/register.php`
- **Public Slideshow:** `http://localhost/visitor-photo-slideshow/slideshow/`
- **Admin Dashboard:** `http://localhost/visitor-photo-slideshow/admin/`

---

### Project Structure

```
visitor-photo-slideshow/
├── index.php                      # Modern landing page
├── .gitignore                     # Git ignore rules (secrets & uploads protected)
├── .env.example                   # Environment configuration template
├── README.md                      # Comprehensive documentation
├── ASSUMPTIONS.md                 # Analysis of requirement gaps & design choices
├── AI_LOG.md                      # Chronological log of AI-assisted interactions & debugging
├── ARCHITECTURE.md                # System diagrams, flows, and security model
├── TESTING.md                     # Test scenarios and execution verification
│
├── config/
│   ├── database.php               # PDO database connection provider
│   ├── config.php                 # Global constants & upload constraints
│   └── environment.php            # Lightweight .env loader
│
├── includes/
│   ├── functions.php              # Utility functions & output escaping
│   ├── auth.php                   # Visitor & Admin session/token auth
│   ├── csrf.php                   # Synchronizer CSRF token protection
│   ├── validation.php             # MIME, file size, and input validators
│   ├── header.php                 # Reusable HTML header & assets
│   ├── footer.php                 # Reusable HTML footer & script bindings
│   └── navbar.php                 # Global navigation bar
│
├── visitor/
│   ├── register.php               # Visitor registration & multi-photo upload form
│   ├── access.php                 # Returning visitor login (Token / PIN)
│   ├── dashboard.php              # Visitor private dashboard & gallery
│   └── delete-photo.php           # Secure individual photo deletion handler
│
├── slideshow/
│   └── index.php                  # Fullscreen public slideshow display
│
├── admin/
│   ├── login.php                  # Host / Admin authentication
│   ├── logout.php                 # Admin session termination
│   ├── dashboard.php              # Overview statistics & recent uploads
│   ├── visitors.php               # Visitor management table
│   ├── photos.php                 # Photo moderation grid (approve/hide/delete)
│   └── actions/
│       ├── toggle-approval.php    # Toggle photo visibility in slideshow
│       └── delete-photo.php       # Administrative photo deletion
│
├── api/
│   ├── upload.php                 # Multi-part AJAX upload endpoint
│   ├── slideshow.php              # JSON feed of approved & consented photos
│   └── visitor-photos.php         # Returning visitor photo feed
│
├── assets/
│   ├── css/
│   │   └── style.css              # Custom styling, transitions, slideshow HUD
│   └── js/
│       ├── app.js                 # Global utilities & toast notifications
│       ├── upload.js              # Drag-drop, preview, client-side validation
│       └── slideshow.js           # Fullscreen, autoplay, keyboard navigation
│
├── database/
│   ├── schema.sql                 # MySQL DDL (tables, foreign keys, indexes)
│   └── seed.sql                   # Default admin user & slideshow settings
│
└── uploads/
    ├── .gitkeep                   # Ensures directory is tracked in Git
    └── .htaccess                  # Hardened security: disables PHP script execution
```

---

### Default Administrative Credentials

- **Username:** `admin`
- **Password:** `Admin@2026!` *(Configured via `database/seed.sql` with `password_hash()`)*

---

### GitHub Repository
- **Remote Origin:** `https://github.com/darshakj07/visitor-photo-slideshow`
