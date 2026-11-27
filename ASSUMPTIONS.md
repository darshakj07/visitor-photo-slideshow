# ASSUMPTIONS.md
## MCA Assignment 1: Visitor Photo Slideshow
### Architectural, Functional, Security, and Privacy Assumptions

This document defines the explicit assumptions, design choices, requirement clarifications, and operational boundaries made during the design and development of the **Visitor Photo Slideshow** application.

---

### 1. Returning Visitor Identification & Authentication

- **Requirement Gap:**
  The specification mandates: *"If a visitor returns, the slideshow also includes their previous photos. Implement a reasonable returning visitor identification mechanism. Do not treat email alone as secure authentication."* However, standard consumer event visitors cannot be burdened with multi-step SMS OTPs, email verification loops, or heavy enterprise password setups during an in-person event or exhibition.

- **Assumption:**
  Visitors require a seamless yet cryptographically safe mechanism to manage their photos without needing a traditional username/password registration wall.

- **Implementation Decision:**
  We implement a hybrid cryptographic token and dual-factor credential architecture:
  1. **Secure Visitor Token (`token`):** Upon initial registration/upload, the backend generates a cryptographically secure 64-character hexadecimal token (`bin2hex(random_bytes(32))`).
  2. **Hash-at-Rest Storage:** The raw token is hashed using SHA-256 before storage in the `visitors.token_hash` database column. The plaintext token is never stored in the database.
  3. **Direct Secure Access Link & Session Cookie:** Upon registration, the plaintext token is securely set in an HTTP-Only, SameSite session cookie and displayed to the visitor as a private bookmarkable "Access Key / Magic Link" (`/visitor/access.php?key=...`).
  4. **Access PIN (Passcode):** In addition to the magic link, visitors can provide or receive a memorable 6-digit access PIN (hashed via `password_hash()` in the database). Returning visitors on a different device can authenticate using their **Email + Access PIN** or **Access Key**.
  5. **Photo Aggregation:** When a recognized returning visitor uploads new photos, the system associates the new photos with the same visitor ID, aggregating their entire collection under one profile.

- **Reason:**
  Email alone is insecure because anyone could enter another person's email to view, delete, or manipulate their photos. The dual-mechanism (Secure Token + PIN) prevents unauthorized account takeover while maintaining high usability during live events.

- **Limitations:**
  If a visitor loses both their Access Key URL and their PIN and does not retain their browser session, automated self-service recovery without a configured SMTP email server is limited to host/admin intervention.

---

### 2. Privacy, Consent, and Public Display Policy

- **Requirement Gap:**
  Photos are projected onto public screens or TVs. The specification requires explicit, un-preselected consent and recording consent withdrawal.

- **Assumption:**
  1. Visitors must be fully aware that approved photos will be broadcast publicly on screens visible to attendees.
  2. Public display must strictly be an opt-in choice. If consent is not provided, photos may be uploaded to the visitor's private album but will be withheld from the public slideshow stream.
  3. Visitors must be granted sovereign control over their media, including real-time consent withdrawal and individual photo deletion.

- **Implementation Decision:**
  1. The consent checkbox on the registration and upload form is **unchecked by default**.
  2. Consent details are tracked in a dedicated `consent_records` audit table storing: visitor ID, consent type (`public_slideshow`), status (`granted` or `withdrawn`), display name permission (`display_name_permitted`), client IP, user agent, and timestamps (`granted_at`, `withdrawn_at`).
  3. When consent is withdrawn from the Visitor Dashboard, the slideshow API immediately excludes all photos by that visitor from future rotation cycles.
  4. The slideshow API strictly filters with `WHERE c.consent_status = 'granted' AND p.is_approved = 1`.
  5. Visitor email addresses are strictly classified as private data and are never exposed in any public API, DOM element, or slideshow caption. Only the visitor's display name is shown (if permitted).

- **Limitations:**
  Photos already rendered in an active browser tab for the currently playing 8-second slide will complete their transition before the next dynamic poll cycle drops withdrawn items.

---

### 3. File Upload Validation & Security Controls

- **Requirement Gap:**
  User-uploaded images can be vectors for Remote Code Execution (RCE), polyglot exploits, cross-site scripting (XSS), path traversal, and denial of service (server storage exhaustion).

- **Assumption:**
  Only genuine web image formats (JPEG, PNG, WebP) should be accepted. File extension alone is untrusted user input.

- **Implementation Decision:**
  1. **Multi-layer MIME & Content Verification:**
     - Client-side validation: file size, extension, MIME type, and real-time image preview.
     - Server-side validation:
       - Checks PHP `$_FILES['photos']['error']`.
       - Validates file size against a strict 10 MB per-file threshold.
       - Enforces a maximum batch limit of 10 images per submission.
       - Validates MIME type using PHP's `finfo_file(FILEINFO_MIME_TYPE)`.
       - Validates image integrity using `getimagesize()` to confirm the file is a readable graphic bitmap.
  2. **Filename Sanitization & Path Traversal Prevention:**
     - The original client-provided filename is recorded for display metadata only.
     - On disk, files are stored using a random 32-character hexadecimal string (`bin2hex(random_bytes(16)) . '.' . $ext`).
     - Directory paths are fixed server-side; user input cannot alter target directories.
  3. **Execution Prevention:**
     - Upload directory contains an `.htaccess` file disabling PHP execution (`php_flag engine off`, `SetHandler default-handler`).
     - Uploads are served through safe paths or controlled streaming endpoints.

- **Limitations:**
  Advanced image steganography (hidden data within valid image payloads) is outside the scope of an assignment photo wall and is mitigated by avoiding file execution.

---

### 4. Concurrency & High-Traffic Handling

- **Requirement Gap:**
  Multiple visitors may upload batches of photos simultaneously during event rushes.

- **Assumption:**
  Database collisions, partial uploads, or race conditions must not corrupt state or leave orphaned files on disk.

- **Implementation Decision:**
  1. **UUID/Cryptographic Filenames:** Every uploaded file receives a globally unique name using `random_bytes()`, guaranteeing zero disk collision during simultaneous writes.
  2. **Atomic Database Transactions:** Multi-file inserts execute inside a PDO transaction (`beginTransaction()`, `commit()`, `rollBack()`).
  3. **Rollback File Cleanup:** If any database insert fails during a multi-photo batch, all files moved to the upload directory for that request are immediately unlinked from disk before the transaction rolls back.
  4. **Dynamic Playlist Polling:** The public slideshow periodically polls `/api/slideshow.php` (every 25 seconds) to fetch the complete, current approved playlist. The client-side slideshow engine reconciles changes dynamically, preserving the active slide during playback while seamlessly integrating new arrivals and dropping unapproved or withdrawn photos.

---

### 5. Deployment & Runtime Environment

- **Assumption:**
  - Running on local XAMPP with PHP 8.2+ and MariaDB/MySQL.
  - Port 3306 on the host system may be occupied by an existing local MySQL 8.0 service; our configuration supports easy environment switching to port 3307 or 3306 via `.env` and `config/database.php`.
  - Apache is serving the application at `http://localhost/visitor-photo-slideshow/`.
