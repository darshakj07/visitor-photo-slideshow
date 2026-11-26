# TESTING.md
## Visitor Photo Slideshow - Test Strategy & Execution Matrix
### MCA Assignment 1: Programming and Debugging Using LLMs

This document defines the formal test scenarios, test cases, automated test scripts, and execution records for the **Visitor Photo Slideshow** application.

---

### Test Execution Matrix

| Test ID | Scenario | Steps / Input | Expected Result | Actual Result | Status | Notes |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **TC-ENV-01** | Database Connectivity | Connect via PDO to MariaDB on configured port (3307/3306) | Connection established, returns server version string | Successfully connected to `10.4.32-MariaDB` | **PASS** | Handles custom port config gracefully |
| **TC-SEC-01** | Git Secret Hygiene | Inspect staged files and git tracking | No `.env`, passwords, or visitor photo files tracked | `.gitignore` active, verified via `git status` | **PASS** | Verified prior to initial commit |
| **TC-REG-01** | Visitor Registration (New) | Enter Name, valid Email, check Consent, select 2 photos (<10MB) | Records created in `visitors`, `consent_records`, and `photos`; redirects to dashboard | Pending execution in Phase 4 | *PENDING* | |
| **TC-REG-02** | Validation: Empty Name | Submit form with empty name | Frontend validation highlights field; backend returns 422 JSON error | Pending execution in Phase 4 | *PENDING* | |
| **TC-REG-03** | Validation: Invalid Email | Submit `user@invalid` or plain text | Validation error returned, no records inserted | Pending execution in Phase 4 | *PENDING* | |
| **TC-VAL-01** | File Size Limit Enforcement | Upload image > 10MB | Client & Server reject file with descriptive error | Pending execution in Phase 4 | *PENDING* | |
| **TC-VAL-02** | Batch Limit Enforcement | Select 11 images (> 10 limit) | Upload blocked, alert indicates max 10 photos per submission | Pending execution in Phase 4 | *PENDING* | |
| **TC-VAL-03** | MIME Spoofing Prevention | Rename `.php` or `.exe` file to `.jpg` and upload | Backend `finfo` & `getimagesize()` detect spoofing and reject file | Pending execution in Phase 4 | *PENDING* | |
| **TC-RET-01** | Returning Visitor Recognition | Submit upload with existing visitor email and matching access key/PIN | New photos attached to existing `visitor_id`; photo count increments | Pending execution in Phase 4 | *PENDING* | |
| **TC-RET-02** | Returning Visitor Unauthorized | Attempt to access visitor dashboard with invalid token | Access denied; 403 Forbidden or redirect to access page | Pending execution in Phase 4 | *PENDING* | |
| **TC-CON-01** | Consent Checkbox Unselected | Load registration form | Consent checkbox is unchecked by default | Pending execution in Phase 2 | *PENDING* | |
| **TC-CON-02** | Consent Withdrawal | Visitor clicks "Withdraw Consent" on dashboard | `consent_records.consent_status` updated to `withdrawn`; photos disappear from public slideshow API | Pending execution in Phase 4 | *PENDING* | |
| **TC-SLI-01** | Public Slideshow Auto-Cycle | Open `/slideshow/` with approved photos | Photos crossfade every 8 seconds automatically | Pending execution in Phase 4 | *PENDING* | |
| **TC-SLI-02** | Public Slideshow Fullscreen & Key Controls | Press Space (pause/play), Arrow keys (prev/next), F (fullscreen) | Slideshow responds accurately to keyboard navigation | Pending execution in Phase 4 | *PENDING* | |
| **TC-SLI-03** | Empty Slideshow Handling | Open `/slideshow/` when no approved photos exist | Displays welcoming empty state card without JS crashes | Pending execution in Phase 4 | *PENDING* | |
| **TC-ADM-01** | Admin Authentication | Login with valid admin credentials | Session authenticated, redirected to `/admin/dashboard.php` | Pending execution in Phase 4 | *PENDING* | |
| **TC-ADM-02** | Admin Photo Moderation | Admin clicks "Hide from Slideshow" on a photo | `is_approved` set to 0; photo immediately excluded from public feed | Pending execution in Phase 4 | *PENDING* | |
| **TC-CONC-01** | Concurrent Simultaneous Uploads | Simulate two simultaneous multi-photo uploads | Both batches succeed with unique filenames, no file overwrite or DB deadlock | Pending execution in Phase 5 | *PENDING* | |
