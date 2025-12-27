# VAPT Security - Superadmin Guide (v4.1.2)

**CONFIDENTIAL** - Do not distribute to clients.

## Domain Admin Access

The "Domain Admin" control panel is hidden and accessible only to the authorized Superadmin.

**Direct Access URL:**
`[Your Domain]/wp-admin/admin.php?page=vapt-domain-control`

### Access Requirements:
1.  **Or**, you can access the page directly without logging in. You will be prompted to verify your identity via an OTP sent to your registered Superadmin email.
2.  Once verified, a secure session cookie grants access for 24 hours.

### Configuration Generator
Use the "Domain Admin" page to:
*   Generate `vapt-locked-config.php` files for other domains.
*   Download "Client Zips" that contain the plugin and configuration but exclude this guide and other development files.
