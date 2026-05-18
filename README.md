# Tiger Clubs Portal

A lightweight PHP-based portal for browsing and managing student clubs. The front-end lists clubs from JSON + per-club `drawer.json` metadata and supports Google OAuth login. Admins and club executives can edit club data and upload banner images.

Primary goals:
- Simple static-like club pages driven by JSON files per-club
- Admin UI to manage clubs and assign roles
- Google OAuth-based sign-in (students / staff)
- Small set of APIs for club data, images, and updates

---

## Project structure (key files)

- `index.php` — Main public UI that loads clubs and shows a club drawer.
- `styles.css`, `script.js` — Front-end styles and behavior.
- `clubs.json` — Array of club slugs (one folder per slug in project root).
- `scripts/init_club_dirs.php` — CLI helper to create per-club directories and default `drawer.json` and `main.png` placeholders.

Auth
- `auth/config.php` — OAuth and domain settings (CLIENTID / CLIENTSECRET placeholders).
- `auth/login.php` — Login page (student vs staff sign-in links).
- `auth/callback.php` — OAuth token / profile exchange and user creation/upsert.
- `auth/logout.php` — Ends session.
- `auth/db.php` — PDO connection (currently contains credentials; see setup notes).
- `auth/club-utils.php` — Helper functions for club file paths, validation, and permission checks.

Admin
- `admin/dashboard.php` — Admin UI for editing clubs, uploading banners, bulk role assignment.
- `admin/assign-role.php` — POST endpoint for bulk role assignment and club exec updates.
- `admin/get-club.php` — Returns a single club's `drawer.json` and banner info (AJAX).

API
- `api/clubs.php` — Returns array of clubs (reads `clubs.json` then each club's `drawer.json`).
- `api/image.php` — Image resizing/caching endpoint (accepts `path`, `w`, `h`).
- `api/update-club.php` — POST endpoint to update `drawer.json` for a club (admin/executive only).
- `api/upload-banner.php` — POST endpoint to upload `main.png` for a club (admin/executive only).
- `api/_club-api-common.php` — Common helpers for API endpoints (session checks, error helpers).

Other
- Each club folder (e.g. `blue-note/`) contains `drawer.json`, `main.png`, `documents/`, etc.
- `cache/images/` — image cache created/used by `api/image.php`.

---

## Requirements

- PHP 8.0 or newer (uses typed unions, `str_ends_with`, etc.)
- PHP extensions:
    - `pdo_mysql` (PDO + MySQL)
    - `gd` (optional but recommended; used to create placeholder images and process images)
    - `fileinfo`
    - `json`, `mbstring`, `session`
- Writable filesystem permissions for:
    - project root (to create club directories if using `init_club_dirs.php`)
    - `cache/images/` (image cache)
    - per-club folders for `main.png`, `drawer.json` updates
- A MySQL (or compatible) database for `users` table.

---

## Setup / Local development

1. Clone the repo into your webroot (or run PHP built-in server for development).
2. Configure PHP / extensions (see Requirements). Ensure `upload_max_filesize` and `post_max_size` are adequate for banner images.
3. Create the database and users table (example SQL below).
4. Edit `auth/db.php` to set your database credentials or replace its contents to load from environment variables (recommended).
5. Edit `auth/config.php`:
    - Set `google_client_id`, `google_client_secret` and `google_redirect_uri`.
    - Set `student_domain` and `staff_domain` to your domains.
6. Initialize club directories and default `drawer.json` files:
    - From project root run (PowerShell):
      ```powershell
      php .\scripts\init_club_dirs.php
      ```
    - This will create folders for each slug in `clubs.json`, create basic `drawer.json`, `documents/`, and placeholder `main.png` when possible.
7. Ensure the web server user can write to `cache/images/` (the `api/image.php` will create it automatically when needed).
8. Start local PHP server (optional) for quick testing:
   ```powershell
   # Run from project root; serves on http://localhost:8000
   php -S localhost:8000
   ```
   Then open `http://localhost:8000/index.php`.

---

## Database schema

The application expects a `users` table. The code uses queries like `SELECT * FROM users WHERE email = ?` and inserts fields `name`, `email`, `google_id`, `role`. A minimal schema:

```sql
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  google_id VARCHAR(255) DEFAULT '',
  role ENUM('student','advisor','executive','admin') NOT NULL DEFAULT 'student',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Adjust types to match your conventions. The app does not currently require a password because authentication is via Google OAuth.

---

## OAuth / auth config

- `auth/config.php` contains:
    - `google_client_id`, `google_client_secret`
    - `google_redirect_uri` (must match OAuth consent screen / Google credentials)
    - `student_domain` and `staff_domain` used to restrict allowed sign-ins (the login page builds two link flows: `state=student` or `state=staff`)
- Set these to your values. Ensure the redirect URI is reachable by Google (e.g., `https://yourdomain/auth/callback.php`).

---

## How the roles & permissions work

- Roles: `student`, `advisor`, `executive`, `admin`.
- `admin` can manage all clubs and access the admin dashboard (`admin/dashboard.php`).
- `executive` can manage clubs they are assigned to — per-club `drawer.json` contains `executiveEmails` (lowercase).
- Executives editing/updating club data use `api/update-club.php` and `api/upload-banner.php`; both call `api_require_managed_club()` which checks session and club membership using `auth/club-utils.php`.

---

## API Endpoints (summary)

- GET `/api/clubs.php`
    - Returns JSON array of clubs read from `clubs.json` + each `drawer.json`. Each item includes `image` (path) and `dirName`.

- GET `/admin/get-club.php?club=CLUB_SLUG`
    - Returns the club's drawer data and `image` path. Requires a logged-in user with permission to manage that club (executive or admin).

- POST `/api/update-club.php`
    - Form POST, requires session and permission.
    - Form fields:
        - `clubDir` (string) — slug directory
        - `data` (string) — JSON encoded object containing allowed fields to update (see `api/update-club.php` `allowedFields` list)
    - Response: JSON success/error.

- POST `/api/upload-banner.php`
    - Form POST with multipart file field `banner`, and `clubDir`.
    - Allowed MIME types: PNG, JPEG, WebP. Will save as `{clubDir}/main.png` and clear `cache/images/`.

- GET `/api/image.php?path=/club-slug/main.png&w=WIDTH&h=HEIGHT`
    - Resizes/crops to requested dimensions (caching in `cache/images/`), falls back to original if GD not available.
    - Security: path traversal prevented; only files under project root are served.

Example: fetch clubs
```bash
curl 'http://localhost:8000/api/clubs.php'
```

Example: update club (needs session cookies from authenticated admin/executive)
```bash
curl -X POST 'http://localhost:8000/api/update-club.php' \
  -F 'clubDir=blue-note' \
  -F 'data={"name":"Blue Note Jazz Club","type":"Arts & Culture","summary":"A jazz group","members":10}'
  # Include session cookie header to act as logged-in admin/executive
```

---

## Developer notes / key functions

- `auth/club-utils.php`
    - `club_is_valid_slug($slug)` — validates slug chars `/^[a-z0-9\-_]+$/`
    - `club_drawer_path($slug)` — location of `drawer.json`
    - `club_load_drawer($slug)` — loads and decodes JSON for a club
    - `club_user_is_executive_of($slug, $email)` — checks `executiveEmails` list
    - `club_resolve_for_management($user, $clubDir)` — validates permission and returns drawer data

- `api/_club-api-common.php`
    - `api_require_managed_club()` — ensures user session and admin/executive rights; used by `update-club.php` and `upload-banner.php`.

---

## Deployment notes & security

- Never keep production DB credentials or OAuth client secrets in committed files. Replace `auth/config.php` and `auth/db.php` to read from environment variables in production.
- Ensure proper permissions: webserver must have write access only where necessary (`cache/images/`, per-club folders). Avoid giving wide write access to repo files.
- The OAuth redirect must be HTTPS in production.
- The code uses basic session-based auth. Consider:
    - Enforcing SameSite cookies, secure cookies, session cookie lifetimes
    - CSRF protection on admin POST endpoints (currently relies on session + role check; consider adding CSRF tokens)
- `api/image.php` includes simple path traversal protections but always validate inputs when adding new endpoints.

---

## Troubleshooting

- "Could not load club data" on `index.php`:
    - Confirm `api/clubs.php` is reachable and `clubs.json` exists and is valid JSON.
- Banner uploads failing:
    - Check `upload_max_filesize` and `post_max_size` in `php.ini`.
    - Check `cache/images/` ownership and permissions if caching fails.
- OAuth login fails:
    - Ensure `auth/config.php` values are correct and that the OAuth credential configured in Google Cloud Console includes the redirect URI in allowed redirect URIs.
- Database connection errors:
    - Check credentials in `auth/db.php`. Use the SQL schema above to create `users` table.

---

## Useful commands (PowerShell)

Run init script (from project root):
```powershell
php .\scripts\init_club_dirs.php
```

Run built-in PHP server for quick testing:
```powershell
php -S localhost:8000
# then open http://localhost:8000/index.php
```

Create DB table (MySQL):
```powershell
# using mysql CLI (replace user and db accordingly)
mysql -u root -p -e "CREATE DATABASE club_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p club_portal < create_users_table.sql
# or paste the CREATE TABLE SQL from this README
```

---

## Contributing

- Use `scripts/init_club_dirs.php` when changing `clubs.json` to scaffold new club folders.
- Keep sensitive credentials out of version control. Replace `auth/config.php` and `auth/db.php` with environment-aware loading before production.

---

## License

This project is licensed under the [MIT License](LICENSE).
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

---