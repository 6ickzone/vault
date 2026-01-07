# ⚡ Reflected XSS Fuzzer

A lightweight, web-based tool designed to test Reflected XSS vulnerabilities efficiently using PHP and cURL.

##  Files

| File | Style | Description |
| :--- | :--- | :--- |
| `1.php` | **Clean SaaS** | Professional, minimal design. |
| `2.php` | **Neon Void** | Dark, cyberpunk aesthetic. |

> **Note:** Both files share the exact same scanning logic.

##  Key Features

- **Custom Injection:** Use the `FUZZ` keyword to define where the payload goes.
- **Dual Methods:** Supports **GET** and **POST** requests.
- **Stealth:** Rotates User-Agents and supports delay to bypass basic rate-limiting.
- **Metrics:** Displays response time (latency) to detect WAF tarpits.

##  Usage Guide

**1. Requirements**
Ensure `extension=curl` is enabled in your `php.ini`.

**2. GET Injection**
Place `FUZZ` in the URL parameter.
- **Target:** `http://target.com/search.php?q=FUZZ`
- **Method:** `GET`

**3. POST Injection**
Place `FUZZ` in the Body Data.
- **Target:** `http://target.com/login.php`
- **Method:** `POST`
- **Post Data:** `user=admin&pass=FUZZ`

##  Disclaimer

**For Educational Research Only.**
This tool is created for security analysis. The author (**6ickzone**) is not responsible for any misuse.
