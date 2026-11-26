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
│   ├── schema.sql                 # MySQL DDL (tables, unique email, foreign keys)
│   ├── seed.sql                   # Default admin user & slideshow settings
│   └── migrate_unique_email.php   # Safe CLI-only database migration script
│
├── tests/
│   ├── run_all_tests.php          # Master automated test suite runner (10 suites)
│   ├── test_database.php          # Database connectivity & schema integrity
│   ├── test_validation.php        # Input, MIME spoofing, and oversized upload tests
│   ├── test_visitor_workflow.php  # Registration, auth, upload, and consent tests
│   ├── test_admin.php             # Admin auth & photo approval/moderation tests
│   ├── test_concurrency.php       # Multi-visitor concurrent upload tests
│   ├── test_account_takeover_prevention.php # Account hijacking defense tests
│   ├── test_admin_redirect_and_actions.php  # Open redirect & action handler tests
│   ├── test_upload_hardening.php  # .htaccess script execution & browsing tests
│   ├── test_slideshow_transition.php        # Slideshow queue state machine tests
│   └── test_unique_visitor_email.php        # DB unique constraint enforcement tests
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

### Automated Test Suite Execution

The application comes with 10 comprehensive automated test suites covering environment connectivity, input and MIME validation, oversized upload rejection, concurrent multi-visitor uploads, returning visitor authentication, consent withdrawal, admin moderation & CSRF verification, open redirect defenses, uploads directory hardening, slideshow empty-queue state transitions, and database-level unique email constraints.

To execute the entire test suite in your terminal:
```bash
php tests/run_all_tests.php
```

All 10 test suites will run and display a unified verification matrix. For detailed test cases and execution results, see [TESTING.md](TESTING.md).

---

### Step-by-Step Live Demonstration Guide

When presenting this application to your professor, follow this demonstration workflow:

1. **Landing Page:**
   - Navigate to `http://localhost/visitor-photo-slideshow/`.
   - Highlight the modern digital guestbook UI, responsive layout, feature highlights, and privacy explanation.
2. **Visitor Registration & Initial Upload:**
   - Click **"Share Your Photos"**.
   - Show that the consent checkbox is **unselected by default**.
   - Enter Name (e.g., *Alex Morgan*), Email (`alex@example.com`), and an optional PIN (e.g., `123456`).
   - Drag and drop 2-3 images. Point out the instant previews, file size badges, and individual removal buttons.
   - Check the consent box and click **"Upload & Join Public Slideshow"**.
3. **Public Slideshow:**
   - Open `http://localhost/visitor-photo-slideshow/slideshow/index.php` in a separate window or tab.
   - Demonstrate the automatic 8-second crossfade, ambient blurred backdrop, and attribution caption (*Photo by Alex Morgan*).
   - Press **Space** to pause/resume, **Right/Left arrow keys** to change slides manually, and **F** for fullscreen TV mode.
4. **Returning Visitor Identification & Consent Control:**
   - Open `http://localhost/visitor-photo-slideshow/visitor/access.php`.
   - Log in using Email (`alex@example.com`) and PIN (`123456`) or paste the Magic Access Key.
   - In the **Visitor Dashboard**, demonstrate that previous photos are displayed.
   - Click **"Withdraw Consent"**.
   - Return to the Public Slideshow tab: within the next poll cycle, Alex's photos will automatically disappear from the live screen!
   - Click **"Grant Consent"** to restore them to the live screen.
5. **Host / Admin Panel & Moderation:**
   - Navigate to `http://localhost/visitor-photo-slideshow/admin/login.php`.
   - Log in with `admin` / `Admin@2026!`.
   - Show the live dashboard metrics (Total Visitors, Uploads, Active Consents, Live on Screen).
   - Under **Photo Moderation**, click **Hide** on a photo to take it off the slideshow, or **Delete** to unlink it from disk.
   - Adjust the **Slide Duration** setting (e.g., from 8s to 5s) and click Save.

---

### Known Limitations

1. **Email Delivery in Local XAMPP:**
   - Local offline XAMPP environments typically lack an active outbound SMTP mail transport agent. Consequently, returning visitor identification is engineered using high-entropy cryptographic access tokens and access PINs rather than outbound email verification loops.
2. **Slideshow Transition during Consent Withdrawal:**
   - A photo currently being projected in an active slide will finish its current 8-second display before the asynchronous poll drops it from future queue rotations.

---

### GitHub Repository
- **Remote Origin:** `https://github.com/darshakj07/visitor-photo-slideshow`
