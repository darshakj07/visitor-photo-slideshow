# AI_LOG.md
## AI-Assisted Development & Debugging Log
### MCA Assignment 1: Programming and Debugging Using LLMs

This log documents authentic interactions between the student (Developer / Reviewer) and the AI Assistant (Antigravity), detailing design reviews, identified code defects, detection methods, debugging steps, corrections, and test verifications.

---

### Interaction 1: Environment Discovery & Port Binding Conflict

- **Date:** 2026-11-26
- **Context:** Project Initialization & Database Connectivity Inspection.
- **Prompt:**
  "Check whether Git is initialized, verify remote URL, and test connection to MySQL database in local XAMPP environment."
- **AI Initial Approach:**
  The AI attempted to start MySQL and connect via standard PDO `mysql:host=localhost:3306;root;""`.
- **Review & Detected Issue:**
  1. The connection failed with `SQLSTATE[HY000] [1045] Access denied for user 'root'@'localhost' (using password: NO)`.
  2. Inspecting the system revealed that a background Windows service `MySQL80` (MySQL 8.0) was already occupying TCP port 3306 with strict authentication, preventing XAMPP's native MariaDB from binding to default port 3306.
- **Debugging Process:**
  - Ran `Get-CimInstance Win32_Service` to locate running database daemons.
  - Examined `C:\xampp\mysql\data\mysql_error.log` which logged: `Can't start server: Bind on TCP/IP port. Got error: 10048: Only one usage of each socket address is normally permitted.`
  - Tested launching XAMPP's MariaDB on alternate port 3307 (`--port=3307`).
- **Correction:**
  - Configured XAMPP MariaDB to operate on port 3307.
  - Designed the application's `.env.example` and `config/database.php` to accept dynamic `DB_PORT` (defaulting to 3307 or 3306) with an automatic fallback mechanism so the student and professors can run seamlessly on any standard or dual-engine machine.
- **Verification:**
  Executed PHP CLI PDO test:
  `php -r "$p = new PDO('mysql:host=127.0.0.1;port=3307', 'root', ''); echo $p->getAttribute(PDO::ATTR_SERVER_VERSION);"`
  Output confirmed: `10.4.32-MariaDB` connected successfully.

---

### Interaction 2: Reviewing Returning Visitor Identification Design

- **Date:** 2026-11-26
- **Context:** Returning Visitor Authentication Architecture.
- **Prompt:**
  "Design a returning visitor identification mechanism that retrieves their previous photos and allows uploading additional ones without treating email alone as secure authentication."
- **AI Initial Proposal:**
  The AI suggested sending a 6-digit OTP code to the visitor's email address on every return visit.
- **Student Review & Flaw Identified:**
  The assignment must run locally inside a standard student XAMPP environment where `mail()` / local SMTP is typically unconfigured or blocked by ISP/firewall. If an email-only OTP workflow was built, live classroom demonstrations would fail due to missing SMTP servers.
- **Debugging & Architectural Pivot:**
  We replaced the reliance on third-party SMTP with a dual-factor cryptographic key architecture:
  1. A 64-character high-entropy cryptographic access token (`bin2hex(random_bytes(32))`), stored as a SHA-256 hash.
  2. A direct "Access / Magic Link" provided to the visitor on completion of upload, alongside an optional 6-digit PIN.
  3. Automatic SameSite session establishment so the current browser remembers the visitor automatically.
- **Correction:**
  Updated `ARCHITECTURE.md` and `ASSUMPTIONS.md` to establish this robust offline-capable zero-dependency mechanism.
- **Verification:**
  Verified that the workflow operates completely offline within XAMPP without requiring external SMTP gateways.

---

### Interaction 3: AI Code Defect - Unconditional `session_regenerate_id()` Call

- **Date:** 2026-11-26
- **Context:** Automated Test Suite Execution on Authentication Module.
- **Prompt:**
  "Implement `includes/auth.php` functions to log in visitors and administrators with session fixation prevention."
- **AI Implementation:**
  The AI wrote `loginVisitor()` and `attemptAdminLogin()` with an unconditional call to `session_regenerate_id(true)`:
  ```php
  function loginVisitor(array $visitor, string $plaintextToken = ''): void {
      session_regenerate_id(true);
      $_SESSION['visitor_id'] = (int) $visitor['id'];
      ...
  }
  ```
- **Error Detection:**
  When executing the automated test suite `tests/test_visitor_workflow.php` and `tests/test_admin.php`, PHP triggered runtime warnings:
  `PHP Warning: session_regenerate_id(): Session ID cannot be regenerated after headers have already been sent in includes/auth.php on line 38`
  `PHP Warning: session_regenerate_id(): Session ID cannot be regenerated after headers have already been sent in includes/auth.php on line 180`
- **Debugging Process:**
  1. Reviewed PHP documentation regarding `session_regenerate_id()`. When output has started (such as in CLI scripts, tests, or before headers are flushed), session regeneration cannot modify cookie headers and fails with a warning.
  2. Identified that checking both `!headers_sent()` and `session_status() === PHP_SESSION_ACTIVE` is mandatory before attempting session ID regeneration.
- **Correction:**
  Refactored both authentication functions in `includes/auth.php`:
  ```php
  if (!headers_sent() && session_status() === PHP_SESSION_ACTIVE) {
      session_regenerate_id(true);
  }
  ```
- **Verification:**
  Re-ran `tests/test_visitor_workflow.php` and `tests/test_admin.php`.
  Both suites executed cleanly with zero warnings and exit code 0.

---

### Interaction 4: Concurrency & Transactional Storage Architecture

- **Date:** 2026-11-26
- **Context:** Concurrent Multi-Visitor Photo Ingestion.
- **Prompt:**
  "Design `api/upload.php` to handle multiple visitors uploading simultaneously without file overwrites or race conditions."
- **AI Proposal & Review:**
  1. The AI initially considered naming files using timestamp prefixes (`time() . '_' . $originalName`).
  2. Student Review: If two attendees upload photos with identical default smartphone filenames (e.g., `IMG_0001.jpg`) at the exact same second, a collision or overwrite could occur.
  3. Solution: Implemented 128-bit cryptographic random hexadecimal naming:
     `$storedName = bin2hex(random_bytes(16)) . '.' . $extension;`
  4. Wrapped in a PDO transaction with an explicit cleanup loop (`unlink()`) in the catch block so that any database failure immediately purges disk files before rollback.
- **Verification:**
  Verified via automated simulation test `tests/test_concurrency.php` where two simultaneous visitors uploaded files with identical original names (`camera_shot_001.png`), resulting in 6 distinct storage files and zero collisions.

---

### Interaction 5: Security Audit Finding - Visitor Account Takeover Vulnerability

- **Date:** 2026-11-26
- **Context:** Security Audit of Visitor Registration & Upload Endpoint (`api/upload.php`).
- **Prompt:**
  "The security audit identified an account takeover vulnerability in `api/upload.php`. When an existing visitor's email address is submitted, the upload endpoint updates the visitor's name or PIN and authenticates the requester without verifying ownership. Fix this vulnerability properly without breaking returning visitor access or registration."
- **AI Initial Code & Vulnerability Mechanism:**
  In the initial implementation, if an attacker entered a victim's registered email on the upload form, the code executed:
  ```php
  if ($visitor) {
      $visitorId = (int) $visitor['id'];
      $updateSql = "UPDATE visitors SET name = ?, updated_at = NOW()";
      if ($pin !== '') {
          $updateSql .= ", access_pin_hash = ?";
          $params[] = password_hash($pin, PASSWORD_BCRYPT);
      }
      ...
  }
  ```
  This allowed an unauthenticated attacker to overwrite the victim's name and PIN, attach arbitrary photos to the victim's account, and gain authenticated access to their private dashboard.
- **Debugging & Security Remediation:**
  1. Identified that account ownership must be rigorously proven before permitting uploads or associating records with an existing email.
  2. Implemented strict dual-condition ownership verification:
     - Case 1: Requester is already authenticated in the active session as that specific visitor (`$currentVisitor['id'] === $visitorId`).
     - Case 2: Unauthenticated requester provides the exact matching PIN for the registered account (`password_verify($pin, $visitor['access_pin_hash'])`).
  3. If ownership cannot be established, the transaction rolls back and returns HTTP 403 Forbidden with a clear prompt directing the visitor to sign in via `/visitor/access.php`.
  4. For verified owners, existing account credentials (name, token, PIN) are never overwritten.
- **Verification:**
  Created `tests/test_account_takeover_prevention.php` covering 7 distinct test cases. All tests passed, confirming that unauthenticated hijack attempts and incorrect PIN submissions are rejected, victim records remain unaltered, and unauthorized deletion is blocked.

---

### Interaction 6: Admin Open Redirect Vulnerability in Action Handlers

- **Date:** 2026-11-26
- **Context:** Host / Admin Action Handlers (`toggle-approval.php` & `delete-photo.php`).
- **Prompt:**
  "Fix the open redirect vulnerability in `admin/actions/toggle-approval.php` and `admin/actions/delete-photo.php`. The previous audit identified that these files accept a POST `return_url` and redirect to it without proper validation."
- **AI Proposed Approach & Attack Vectors Evaluated:**
  The AI evaluated user-controlled `return_url` values and identified that simple string checks or `header("Location: " . $_POST['return_url'])` allow attackers to craft phishing redirects using:
  - Protocol-relative URLs (`//evil.example`)
  - Backslash evasions (`/\\evil.example`, `\\\\evil.example`)
  - Pseudo-protocols (`javascript:`, `data:`, `vbscript:`)
  - HTTP Header Injection via CRLF characters (`%0d%0a`)
  - Off-origin domains with spoofed path segments (`https://evil.example/admin/photos.php`)
- **Correction:**
  Created a dedicated validator `validateAdminReturnUrl(?string $url, string $default): string` in `includes/functions.php`:
  1. Strips non-printable ASCII and control characters (`[\x00-\x1F\x7F]`) to block CRLF injection.
  2. Blocks protocol-relative and backslash-prefixed strings.
  3. Parses the URL via `parse_url()` and strictly validates that scheme, host, and port (if present) match the configured application origin.
  4. Enforces a strict allowlist of permissible admin destination scripts (`dashboard.php`, `photos.php`, `visitors.php`).
  5. Sanitizes permitted query parameters (e.g., `?filter=all|approved|hidden`) and discards arbitrary query strings.
  6. Updated both action handlers to sanitize `return_url` through `validateAdminReturnUrl()`.
- **Verification:**
  Created `tests/test_admin_redirect_and_actions.php` testing 12 distinct attack vectors and legitimate routes. All 12 test vectors were successfully blocked and redirected to safe defaults, while legitimate internal destinations were accepted.

---

### Interaction 7: Uploads Directory Hardening via `.htaccess`

- **Date:** 2026-11-26
- **Context:** Web Server Security Configuration (`uploads/.htaccess`).
- **Prompt:**
  "Secure the `uploads/` directory against directory browsing and unauthorized script execution. The previous audit identified that `uploads/.htaccess` lacks explicit directory listing protection."
- **AI Implementation & Review:**
  1. The existing `.htaccess` contained basic script matching but lacked explicit protection against directory indexing, CGI execution, and multi-version PHP handler overrides.
  2. Furthermore, in Apache 2.4 environments, legacy `Order Deny,Allow` directives should be upgraded to `Require all denied` to prevent execution across different Apache configurations.
- **Correction:**
  Rebuilt `uploads/.htaccess` with multi-layered Apache 2.4+ hardening:
  - `Options -Indexes -ExecCGI` to disable directory browsing and CGI execution.
  - Multi-version PHP engine disabling (`mod_php5`, `mod_php7`, `mod_php8`, `mod_phpx`).
  - `RemoveHandler` and `RemoveType` to strip execution bindings from `.php`, `.phtml`, `.phps`, `.phar`, `.cgi`, `.pl`, `.py`, `.sh`, `.exe`, `.bat`, `.cmd`.
  - `SetHandler default-handler` to force static file serving.
  - Case-insensitive `<FilesMatch>` denying all executable and config extensions via `Require all denied`.
  - Explicit `<FilesMatch>` permitting read access strictly to legitimate image types (`.jpg`, `.jpeg`, `.png`, `.webp`) via `Require all granted`.
- **Verification:**
  Created `tests/test_upload_hardening.php` and conducted live HTTP probes against the local Apache server:
  - Directory index request returned `HTTP 403 Forbidden`.
  - PHP script execution probe returned `HTTP 403 Forbidden` (source code unexecuted).
  - Genuine image file returned `HTTP 200 OK`.

---

### Interaction 8: Slideshow Empty-Queue State Machine Defect

- **Date:** 2026-11-26
- **Context:** Frontend Slideshow Engine (`assets/js/slideshow.js`).
- **Prompt:**
  "Fix the slideshow behavior when it initially has no approved photos but photos become available later through background polling. In `assets/js/slideshow.js`, when the queue changes from empty to populated, the empty state is hidden without displaying the first photo or starting the timer."
- **AI Code Inspection & Defect Analysis:**
  Inspected `fetchPhotos(true)` in `assets/js/slideshow.js`. When photos arrived via background polling and `this.photos` was previously empty, the code executed:
  ```javascript
  if (this.photos.length > 0) {
      this.hideEmptyState();
      const newIdx = this.photos.findIndex(p => p.id === activePhotoId);
      this.currentIndex = (newIdx !== -1) ? newIdx : 0;
  }
  ```
  While `hideEmptyState()` was invoked, `this.showSlide(0)` was never called and `this.startTimer()` was never initiated. As a result, the placeholder disappeared, leaving a black viewport without rendering the first photo or starting automatic slide rotation.
- **Debugging & State Machine Refactoring:**
  Restructured `fetchPhotos(true)` into a complete state machine:
  1. Empty-to-Populated: Hides empty state, immediately renders `showSlide(0)`, and starts the rotation timer (`startTimer()`).
  2. Single-to-Multi Transition: When a second photo arrives while a single photo is playing, starts the rotation timer (which previously stayed idle for single slides).
  3. Already Populated: Preserves the active viewing slide, updates the HUD counter (`1 / N`), and smoothly incorporates new photos into future cycles without jarring reloads.
  4. Moderation Removal Fallback: If the active slide was unapproved or deleted, gracefully falls back to a valid index.
  5. Populated-to-Empty: Restores the empty state placeholder and clears timers if all photos are removed.
- **Verification:**
  Authored `tests/test_slideshow_transition.php` executing the actual `SlideshowApp` JavaScript class within a Node.js test harness. Verified all 6 lifecycle transitions passed with zero timer duplication.
