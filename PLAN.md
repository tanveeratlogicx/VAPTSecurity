# VAPT Security - Improvement Plan & History

## To-Do (2025-12-27 12:13:13)
### Verification
- [x] Verify Settings Save (Ajax) updates "WP-Cron Disabled" status instantly.
- [x] Verify "Diagnostics & Evidence" is above "Server Level Custom Cron".
- [x] Verify "Run Cron Test" shows real-time progress.
- [x] Verify "Reset Counter" clears the backend limit (allowing new tests).

## History / Changelog

### [2025-12-27 12:13:13] Bug Fixes
- [x] **Rate Limiter Configuration**
    - [x] Fixed `VAPT_Rate_Limiter` to use `VAPT_Security::instance()->get_config()` instead of raw `get_option`, ensuring encrypted settings (like `cron_rate_limit`) are correctly read.

### [2025-12-27 12:13:13] UI Layout & Diagnostic Tools
- [x] **Diagnostic Tools Enhancements**
    - [x] **Cron Rate Limit Test**
        - [x] **Reset Button:** Added "Reset Counter" button next to "Run Cron Test".
            - [x] Backend: Added `wp_ajax_vapt_reset_cron_limit` to reset rate limit data for current IP.
            - [x] Frontend: Wired up button to call API and reset UI counters.
        - [x] **Visual Indicator:**
            - [x] Added real-time counters: Generated, Blocked, Allowed.
            - [x] Updated counters dynamically during the test loop.
- [x] **UI Layout Improvements**
    - [x] **Reorder Left Column**
        - [x] Moved "Diagnostics & Evidence" *above* "Server Level Custom Cron".
    - [x] **Swap Grid Columns**
        - [x] Moved "Diagnostics & Evidence" to Left Column.
        - [x] Moved "Cron Rate Limit Test" to Right Column.

### [2025-12-27 12:13:13] Ajaxify Settings
- [x] **Ajaxify Settings Page & Dynamic UI Updates**
    - [x] **Backend Logic (`vapt-security.php`)**
        - [x] Updated `handle_save_settings` to return `diagnostics` data (cron status, etc.).
    - [x] **Frontend Logic (`vapt-security.js`)**
        - [x] Updated form submission to handle JSON response and update UI elements (`#vapt-diag-cron-status`).
    - [x] **Template (`admin-settings.php`)**
        - [x] Added IDs to diagnostic table cells (e.g., `#vapt-diag-cron-status`).
