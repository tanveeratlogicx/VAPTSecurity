# VAPT Security Plugin Architecture

## Overview

The VAPT Security plugin implements a multi-layered security approach to protect WordPress sites from common vulnerabilities:

```mermaid
graph TD
    A[Incoming Request] --> B{Is it wp-cron.php?}
    B -- Yes --> C[WP-Cron Protection]
    B -- No --> D[Rate Limiter]
    C --> E{Rate Limit Check}
    E -- Exceeded --> F[Block Request - 429]
    E -- OK --> G[Process Cron Request]
    D --> H{Rate Limit Check}
    H -- Exceeded --> I[Block Request - 429]
    H -- OK --> J[Input Validation]
    J --> K{Validation Passed?}
    K -- No --> L[Return Error - 400]
    K -- Yes --> M[Process Request]
    M --> N[Security Logging]
    N --> O[Return Response]
```

## Component Details

### 1. WP-Cron Protection Layer

Protects against DoS attacks targeting wp-cron.php:

```mermaid
graph TD
    A[wp-cron.php Request] --> B[Get Client IP]
    B --> C[Check IP Blocking]
    C -- Blocked --> D[Return 429]
    C -- Not Blocked --> E[Apply Rate Limiting]
    E -- Exceeded --> F[Block IP + Return 429]
    E -- Within Limits --> G[Allow Request]
```

#### Key Features:
- IP-based rate limiting specifically for cron requests
- Automatic IP blocking for abusive patterns
- Configurable request limits
- Integration with proxy/Cloudflare IP detection

### 2. Rate Limiting Layer

General purpose rate limiting for all requests:

```mermaid
graph TD
    A[Form/API Request] --> B[Get Client IP]
    B --> C[Retrieve Request History]
    C --> D[Filter Recent Requests]
    D --> E{Within Limits?}
    E -- No --> F[Increment Violation Counter]
    F --> G{Violations > Threshold?}
    G -- Yes --> H[Block IP]
    G -- No --> I[Return 429]
    E -- Yes --> J[Record Request]
    J --> K[Allow Request]
```

#### Key Features:
- Configurable time windows and request limits
- Violation tracking to identify abusive IPs
- Automatic cleanup of old request data
- Separate tracking for regular vs cron requests

### 3. Input Validation Layer

Comprehensive input sanitization and validation:

```mermaid
graph TD
    A[Raw Input Data] --> B[Schema Validation]
    B --> C{Required Fields Present?}
    C -- No --> D[Return Missing Field Error]
    C -- Yes --> E[Type-Specific Sanitization]
    E --> F[Length Validation]
    F --> G{Pattern Matching?}
    G -- Specified --> H[Regex Validation]
    G -- Not Specified --> I[XSS Prevention]
    H --> I
    I --> J[Return Sanitized Data]
```

#### Key Features:
- Schema-based validation approach
- Multiple sanitization levels (Basic, Standard, Strict)
- XSS prevention techniques
- Email, URL, and custom validation
- Regex pattern support

### 4. Security Logging Layer

Monitoring and auditing of security events:

```mermaid
graph TD
    A[Security Event] --> B[Log Event Data]
    B --> C[Store in Options Table]
    C --> D{Log Size Limit Reached?}
    D -- Yes --> E[Remove Oldest Entries]
    D -- No --> F[Complete Logging]
    E --> F
```

#### Key Features:
- Event categorization (form submissions, blocked requests, etc.)
- Statistical reporting dashboard
- Automatic cleanup of old logs
- IP-based event tracking

## Data Flow

### Request Processing Flow

1. **Initial Request Interception**
   - All requests pass through WordPress hook system
   - Special handling for wp-cron.php requests
   - IP address identification with proxy support

2. **Rate Limiting Check**
   - Lookup request history for client IP
   - Apply configured limits
   - Block or allow request based on results

3. **Input Validation**
   - Sanitize all input data
   - Validate against defined schemas
   - Apply XSS prevention measures

4. **Processing**
   - Execute requested functionality
   - Apply additional security checks as needed

5. **Logging**
   - Record security-relevant events
   - Update statistics
   - Maintain audit trail

### Data Storage Architecture

```mermaid
graph TD
    A[WordPress Options Table] --> B[vapt_rate_limit]
    A --> C[vapt_cron_rate_limit]
    A --> D[vapt_blocked_ips]
    A --> E[vapt_security_logs]
    A --> F[vapt_ip_violations]
    A --> G[vapt_security_options]
```

#### Storage Components:
- **vapt_rate_limit**: Tracks regular request patterns
- **vapt_cron_rate_limit**: Tracks cron request patterns
- **vapt_blocked_ips**: List of currently blocked IPs
- **vapt_security_logs**: Security event logs
- **vapt_ip_violations**: IP violation counters
- **vapt_security_options**: Plugin configuration

## Performance Optimizations

### 1. Efficient Data Structures
- JSON-encoded arrays for compact storage
- Timestamp-based filtering for quick lookups
- Limited history retention to control growth

### 2. Scheduled Maintenance
- Hourly cleanup of temporary data
- Daily optimization of stored data
- Automatic removal of expired blocks

### 3. Minimal Overhead
- Lightweight processing for normal requests
- Cached lookups where possible
- Asynchronous operations for non-critical tasks

## Security Best Practices Implemented

### 1. Defense in Depth
- Multiple layers of protection
- Redundant validation mechanisms
- Fail-safe defaults

### 2. Secure Defaults
- Conservative initial settings
- Explicit enablement of features
- Safe fallback behaviors

### 3. Monitoring and Auditing
- Comprehensive event logging
- Statistical analysis capabilities
- Violation tracking for threat identification

## Integration Points

### WordPress Hooks Used
- `init` - For early request interception
- `admin_menu` - For settings page registration
- `admin_init` - For settings registration
- `wp_ajax_*` - For form processing
- `cron_schedules` - For custom intervals

### Compatibility Features
- Works with WordPress object cache
- Supports multisite installations
- Integrates with popular security plugins
- Compatible with CDN and proxy services