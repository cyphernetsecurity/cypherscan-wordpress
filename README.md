# CypherScan for WordPress

Protect WordPress uploads before they reach production and feed completed upload scans into CypherScan Agent.

CypherScan securely scans every uploaded file using a presigned upload workflow and can automatically block suspicious or malicious files before they become available inside WordPress. Starting with v1.1.0, successful upload scans are also reported to CypherScan Agent when the connected CypherScan account has an active Agent subscription.

---

## Features

- Secure presigned upload workflow
- Malware detection
- Secret detection
- Automatic malicious file blocking
- CypherScan Agent upload event reporting
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
- CypherScan Agent subscription for Agent activity reporting

---

## How it works

When a file is uploaded:

1. The plugin requests a presigned upload URL from the CypherScan API.
2. The file is uploaded securely to temporary object storage.
3. CypherScan scans the uploaded object.
4. A scan verdict is returned.
5. The existing allow/block decision is applied.
6. The completed `scanId` is reported to the WordPress Agent source on a best-effort basis.
7. CypherScan validates that `scanId` against its own ScanLog before recording Agent activity.

Agent reporting cannot override the upload decision and does not send the CypherScan API key as event data; the key is used only as the normal Authorization credential.

---

## Architecture

```text
WordPress upload
      |
      v
Presigned secure upload
      |
      v
CypherScan file scan
      |
      +----> allow / block (existing behavior)
      |
      v
WordPress Agent event (scanId)
      |
      v
CypherScan validates ScanLog
      |
      v
Agent Source -> SecurityObservation -> Controller / alerts -> Dashboard
```

---

## Fail Open / Fail Closed

The original upload enforcement behavior is unchanged.

### Fail Open

Uploads continue if the scanning service is temporarily unavailable.

### Fail Closed

Uploads are rejected when the scan cannot be completed.

Agent event reporting itself is always best-effort and never changes the upload result.

---

## License

GPL-2.0-or-later

Copyright (c) 2026 CypherNet Security Inc.

---

## Links

- Website: https://cyphernetsecurity.com
- GitHub: https://github.com/cyphernetsecurity

Built by CypherNet Security Inc.
