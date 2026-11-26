# TESTING.md
## Visitor Photo Slideshow - Test Strategy & Execution Matrix
### MCA Assignment 1: Programming and Debugging Using LLMs

This document defines the formal test scenarios, test cases, automated test scripts, and execution records for the **Visitor Photo Slideshow** application.

---

### Automated Test Runner

All core test suites can be executed via the master test runner:
```bash
php tests/run_all_tests.php
```

---

### Test Execution Matrix

| Test ID | Scenario | Steps / Input | Expected Result | Actual Result | Status | Notes |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **TC-ENV-01** | Database Connectivity | Connect via PDO to MariaDB on configured port (3307/3306) | Connection established, returns server version string | Successfully connected to `10.4.32-MariaDB` | **PASS** | Handles custom port config gracefully |
| **TC-SEC-01** | Git Secret Hygiene | Inspect staged files and git tracking | No `.env`, passwords, or visitor photo files tracked | `.gitignore` active, verified via `git status` | **PASS** | Verified prior to initial commit |
| **TC-REG-01** | Visitor Registration (New) | Enter Name, valid Email, check Consent, select 2 photos (<10MB) | Records created in `visitors`, `consent_records`, and `photos`; redirects to dashboard | Visitor ID and photos inserted atomically within transaction | **PASS** | Verified via `tests/test_visitor_workflow.php` |
| **TC-REG-02** | Validation: Empty Name | Submit form with empty name | Frontend validation highlights field; backend returns 422 JSON error | Backend returns `'Your name is required.'` | **PASS** | Verified via `tests/test_validation.php` |
| **TC-REG-03** | Validation: Invalid Email | Submit `user@invalid` or plain text | Validation error returned, no records inserted | Backend returns `'Please provide a valid email address.'` | **PASS** | Verified via `tests/test_validation.php` |
| **TC-VAL-01** | File Size Limit Enforcement | Upload image > 10MB | Client & Server reject file with descriptive error | Rejects file exceeding 10 MB limit | **PASS** | Verified via `tests/test_validation.php` |
| **TC-VAL-02** | Batch Limit Enforcement | Select 12 images (> 10 limit) | Upload blocked, alert indicates max 10 photos per submission | Rejection verified with max 10 notice | **PASS** | Verified via `tests/test_validation.php` |
| **TC-VAL-03** | MIME Spoofing Prevention | Rename `.php` or script file to `.jpg` and upload | Backend `finfo` & `getimagesize()` detect spoofing and reject file | Spoofed file rejected (`text/x-php`, is_image: false) | **PASS** | Verified via `tests/test_validation.php` |
| **TC-RET-01** | Returning Visitor Recognition | Submit upload with existing visitor email and matching access key/PIN | New photos attached to existing `visitor_id`; photo count increments | Returning visitor authenticated via PIN & Token | **PASS** | Verified via `tests/test_visitor_workflow.php` |
| **TC-RET-02** | Returning Visitor Unauthorized | Attempt to access visitor dashboard with invalid PIN | Access denied; authentication returns null | Incorrect PIN correctly rejected | **PASS** | Verified via `tests/test_visitor_workflow.php` |
| **TC-CON-01** | Consent Checkbox Unselected | Inspect registration form DOM | Consent checkbox is unchecked by default (`required`) | Checkbox is unselected by default | **PASS** | Inspected in `visitor/register.php` |
| **TC-CON-02** | Consent Withdrawal | Visitor clicks "Withdraw Consent" on dashboard | `consent_records.consent_status` updated to `withdrawn`; photos disappear from public slideshow API | Photo immediately excluded from slideshow feed upon withdrawal | **PASS** | Verified via `tests/test_visitor_workflow.php` |
| **TC-SLI-01** | Public Slideshow Auto-Cycle | Query `/api/slideshow.php` with approved + consented photos | Returns JSON list of approved photos with interval and caption | Photo present in feed with attribution caption | **PASS** | Verified via `tests/test_visitor_workflow.php` |
| **TC-SLI-02** | Public Slideshow Controls | Keyboard Space (pause/play), Arrows (prev/next), F (fullscreen) | Slideshow handles key events and advances slides smoothly | Handled in `assets/js/slideshow.js` | **PASS** | Tested and verified in JS engine |
| **TC-SLI-03** | Empty Slideshow Handling | Open `/slideshow/` when no approved photos exist | Displays welcoming empty state card without JS crashes | `#slideshowEmptyState` unhides gracefully | **PASS** | Handled in `slideshow/index.php` |
| **TC-ADM-01** | Admin Authentication | Login with valid and invalid admin credentials | Valid credentials pass, invalid rejected | `Admin@2026!` passes; invalid password rejected | **PASS** | Verified via `tests/test_admin.php` |
| **TC-ADM-02** | Admin Photo Moderation | Toggle photo approval status | `is_approved` toggles between 0 and 1; reflected in slideshow feed | Verified in `admin/actions/toggle-approval.php` | **PASS** | SQL toggle verified |
| **TC-CONC-01** | Concurrent Simultaneous Uploads | Simulate two simultaneous multi-photo uploads with identical original filenames | Both batches succeed with unique random storage names; zero collision | 6 distinct files created with zero disk collisions | **PASS** | Verified via `tests/test_concurrency.php` |
