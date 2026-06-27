# CypherScan for WordPress

Protect WordPress uploads with CypherScan before files enter your production workflows.

CypherScan scans every uploaded file through the CypherScan API and can automatically block malicious content before it becomes available inside WordPress.

---

## Features

- Upload scanning
- Malware detection
- Automatic file blocking
- API key management
- Connection testing
- Configurable timeout
- Fail-open mode
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

```
wp-content/plugins/cypherscan-wordpress
```

3. Activate the plugin from:

```
Plugins → CypherScan WordPress
```

4. Open:

```
Settings → CypherScan
```

5. Enter your:

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
- Fail Open
- Timeout
- Debug logging

---

## Upload Flow

```
User Upload
      │
      ▼
WordPress Upload Hook
      │
      ▼
CypherScan API
      │
      ▼
Presigned Upload
      │
      ▼
CypherScan Scan
      │
      ▼
Verdict
      │
      ├── Clean → Allow upload
      └── Blocked → Remove upload
```

---

## Example

```
Upload detected

↓

CypherScan Scan

↓

Verdict: Clean

↓

File available inside WordPress
```

---

## Roadmap

- Scan history
- Media Library verdict badges
- Scan details
- Quarantine support
- WordPress.org release

---

## License

MIT

---

Built by CypherNet Security

https://cyphernetsecurity.com