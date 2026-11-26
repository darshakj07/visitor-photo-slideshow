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
