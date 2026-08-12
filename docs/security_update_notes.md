# MeshBeacon Security Updates: BunkerWeb WAF

## Overview
We've successfully upgraded the security architecture of the MeshBeacon deployment by placing a Web Application Firewall (WAF) in front of the primary application. 

Since MeshBeacon is designed for hybrid/offline disaster response environments, an infrastructure-level firewall (rather than a cloud WAF like Cloudflare) is the most robust and appropriate solution.

## Changes Implemented
We modified the `docker-compose.yml` to adopt a **Defense in Depth** approach using [BunkerWeb](https://www.bunkerweb.io/):

1. **WAF Container (`waf`)**:
   - Pulled the `bunkerity/bunkerweb:1.5.8` enterprise-grade Nginx WAF.
   - Took over the public-facing port (`$MESHBEACON_PORT` / `8080`).
   - Configured it to reverse proxy clean, inspected traffic to the internal webserver.

2. **Webserver Container (`webserver`)**:
   - Removed the public port exposure.
   - Now uses `expose: "80"` to remain strictly on the internal Docker network.
   - The application logic and Nginx configurations remain completely unchanged, ensuring zero false-positive conflicts with Laravel's complex routing or Livewire components.

3. **Documentation**:
   - Updated the `README.md` Docker services table to explain the new `waf` and `webserver` roles.
   - Added a WAF bullet point to the Security Checklist in the README.

## What it Protects Against
The BunkerWeb WAF uses the industry-standard **OWASP Core Rule Set**. This automatically drops connections attempting:
- **SQL Injection (SQLi)** and **Cross-Site Scripting (XSS)**.
- Known vulnerability exploits (zero-days).
- Brute force attacks on the login portal.
- Malicious bot scraping and DDoS attempts (via integrated rate limiting).

## Status
- **CI/CD Verified**: The changes do not interfere with GitHub Actions (`tests.yml` or `docker-publish.yml`).
- **Committed**: Changes committed to the `feature/bunkerweb-waf` branch.
- **Pull Request**: Submitted as [PR #9](https://github.com/MeshBeacon/meshbeacon/pull/9) to the upstream repository.
