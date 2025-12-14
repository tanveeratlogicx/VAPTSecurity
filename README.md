# VAPT Security

A lightweight WordPress plugin that protects against:
- **DoS via wp‑cron** – disables the default WP‑Cron and schedules a safe daily cron job.
- **Lack of input validation** – strict server‑side validation for all user‑controlled fields.
- **Rate‑limiting on form submissions** – IP‑based throttling and optional CAPTCHA.

## Installation

1. Zip the plugin folder (`vapt-security/`).
2. Upload it to `wp-content/plugins/` via FTP or the WP admin.
3. Activate the plugin from the *Plugins* page.
4. Configure the settings under *Settings → VAPT Security*.

## Usage

* All form submissions sent to the AJAX endpoint `admin-ajax.php?action=vapt_form_submit` will be throttled and validated.
* The settings page uses jQuery‑UI tabs (already bundled with WP), so the UI is instant.

## Development

```bash
# Run unit tests
vendor/bin/phpunit tests/test-rate-limiter.php
