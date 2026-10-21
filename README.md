# Fork notice
This repository contains a fork of the original project [giandonatoinverso/PHP-Dynamic-Qr-code](https://github.com/giandonatoinverso/PHP-Dynamic-Qr-code), which was archived in September 2025. It aims to improve security and functionality. We aim to be backwards compatible, making the new software a drop-in replacement.

---

**PHP Dynamic Qr code** is a script that allows the generation and saving of dynamic and static QR codes. It has a clean, responsive, and user-friendly design. It is based on [AdminLte](https://adminlte.io/), the "Best open source admin dashboard & control panel theme. Built on top of Bootstrap" and [Core PHP Admin Panel](https://github.com/chetans9/core-php-admin-panel), a simple Admin Panel written in core PHP that contains an implementation of general features you might need in your website admin panel like: record management (CRUD), secure authentication, pagination, filters.



LIVE DEMO CURRENTLY NOT AVAILABLE - Will be added once this fork reaches a "stable" state.

~~DOCUMENTATION~~ Not yet updated. Will be added in the future.

# Features

- **#1 Dynamic Qr code generator on GitHub with a database to store Qr codes**
- Create unlimited Qr codes
- Docker compose support
- Control panel with 2 access levels
- Multi-account 
- Dashboard with advanced statistics on Qr codes created and on scans
- Bulk download, bulk delete
- Dynamic Qr code
    - Create, modify, and delete Qr codes
    - You can download your Qr codes when you want
    - URL shortener with redirect
    - Enable or disable the link redirect
- Static Qr code
    - Text QR Code
    - Email QR Code
    - Phone QR Code
    - Sms QR Code
    - Whatsapp QR Code
    - Skype QR Code
    - Location QR Code
    - Vcard QR Code
    - Event/calendar QR Code
    - Bookmark QR Code
    - Wifi QR Code
    - Paypal QR Code
    - Bitcoin QR Code
- Customization of Qr codes
    - 6 formats for images
    - Foreground color
    - Background color
    - 4 levels of precision
    - 10 sizes
- Responsive bootstrap-based design
- Easy to understand and expand code
- Full OOP with classes and well-documented

## What is included

- PHP files
~~- .sql file with sample data~~ Removed in order to use the official MariaDB image used by docker-compose.yml.
- JS files
- CSS files
- Docker compose file

## Setup with docker compose

### Option A: Run using published Docker image
1. Download `docker-compose.yml` and `database/init.sql`.
2. Start the stack (pulls prebuilt image `ghcr.io/filliravaz/php-dynamic-qr-code-ng:latest`):
```bash
docker compose up -d
```

### Option B: Build and run from source repository
1. Clone this repository:
```bash
git clone https://github.com/filliravaz/PHP-Dynamic-Qr-code-NG.git
cd PHP-Dynamic-Qr-code-NG
```
2. Build and start the stack:
```bash
docker compose up -d --build
```

3. Open your browser at http://localhost:80 and log in with the initial superadmin credential (username: `superadmin`, password: `superadmin`). Change this password immediately. The first user to log in must change it. If an unauthorized user logs in with these credentials before you, wipe the database to reset the first-login state.
