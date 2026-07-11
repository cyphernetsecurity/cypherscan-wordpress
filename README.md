# CypherScan for WordPress

Protect WordPress uploads before they reach production.

CypherScan securely scans every uploaded file using a presigned upload workflow and can automatically block suspicious or malicious files before they become available inside WordPress.

---

## Features

- Secure presigned upload workflow
- Malware detection
- Secret detection
- Automatic malicious file blocking
- Configurable fail-open / fail-closed behavior
- API key management
- Connection testing
- Configurable request timeout
- Debug logging
- Native WordPress Settings page

---

## Requirements

- WordPress 6+
- PHP 8+
- CypherScan API key

---

## Installation

1. Clone or download this repository.

2. Copy the plugin into:

```text
wp-content/plugins/cypherscan-wordpress
```

3. Activate the plugin from:

```text
Plugins → CypherScan WordPress
```

4. Open:

```text
Settings → CypherScan
```

5. Configure:

- API Key
- API Base URL

6. Click **Save Settings**.

7. Click **Test Connection**.

---

## Configuration

The plugin supports:

- API Key
- API Base URL
- Block infected uploads
- Fail Open / Fail Closed
- Request timeout
- Debug logging

---

## How it works

When a file is uploaded:

1. The plugin requests a presigned upload URL from the CypherScan API.
2. The file is uploaded securely to temporary object storage.
3. CypherScan scans the uploaded object.
4. A scan verdict is returned.
5. Clean files remain available.
6. Suspicious or malicious files are automatically removed.

---

## Architecture

```text
User Upload
      │
      ▼
WordPress Upload Hook
      │
      ▼
Request Presigned Upload URL
      │
      ▼
Temporary Secure Upload
      │
      ▼
CypherScan Scan
      │
      ▼
Verdict
      │
      ├── Clean ─────► Upload allowed
      │
      └── Blocked ───► Upload removed
```

---

## Example

```text
Upload detected

        │

        ▼

Presigned Upload

        │

        ▼

CypherScan Scan

        │

        ▼

Verdict: Clean

        │

        ▼

File available inside WordPress
```

---

## Fail Open / Fail Closed

CypherScan supports two operating modes.

### Fail Open

Uploads continue if the scanning service is temporarily unavailable.

Recommended for development environments.

### Fail Closed

Uploads are rejected when the scan cannot be completed.

Recommended for production environments requiring strict upload enforcement.

---

## Roadmap

- Scan history
- Media Library verdict badges
- Detailed scan reports
- Quarantine support

---

## License

MIT License

Copyright (c) 2026 CypherNet Security Inc.

See the LICENSE file for details.

---

## Links

- Website: https://cyphernetsecurity.com
- GitHub: https://github.com/cyphernetsecurity

Built by CypherNet Security Inc.